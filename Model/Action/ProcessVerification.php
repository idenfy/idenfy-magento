<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Exception;
use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\Config;
use Idenfy\CustomerVerification\Model\Verification;
use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

/**
 * Handles the Idenfy verification webhook
 *
 * @see https://documentation.idenfy.com/kyc/webhooks
 * @see https://documentation.idenfy.com/security/callback-signing
 */
class ProcessVerification
{
    public const VERIFICATION_STATUS_KEY = 'status';
    public const VERIFICATION_OVERALL_STATUS_KEY = 'overall';
    public const VERIFICATION_CLIENT_ID_KEY = 'clientId';
    public const VERIFICATION_SCAN_REF_KEY = 'scanRef';
    public const VERIFICATION_FINAL_KEY = 'final';
    public const VERIFICATION_SUCCESS_STATUS = 'APPROVED';
    public const SIGNATURE_HEADER = 'Idenfy-Signature';
    public const LOGGER_CONTEXT = ['source' => 'webhook'];

    /** @var Request */
    private Request $request;

    /** @var VerificationRepositoryInterface */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var Json */
    private Json $jsonSerializer;

    /** @var Config */
    private Config $config;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param Request $request
     * @param VerificationRepositoryInterface $verificationRepository
     * @param Json $jsonSerializer
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Request $request,
        VerificationRepositoryInterface $verificationRepository,
        Json $jsonSerializer,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->verificationRepository = $verificationRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return string[]
     * @throws WebapiException
     */
    public function execute(): array
    {
        $rawBody = (string) $this->request->getContent();

        $this->validateSignature($rawBody);

        try {
            $requestData = $this->jsonSerializer->unserialize($rawBody);
        } catch (InvalidArgumentException $e) {
            $this->logger->error('Unable to process webhook, request body is not valid JSON', self::LOGGER_CONTEXT);
            throw new WebapiException(__('Invalid request body.'), 0, WebapiException::HTTP_BAD_REQUEST);
        }

        $clientId = is_array($requestData) ? ($requestData[self::VERIFICATION_CLIENT_ID_KEY] ?? null) : null;
        $scanRef = is_array($requestData) ? ($requestData[self::VERIFICATION_SCAN_REF_KEY] ?? null) : null;
        $verificationStatus = is_array($requestData) ? ($requestData[self::VERIFICATION_STATUS_KEY] ?? null) : null;
        $overallStatus = is_array($verificationStatus)
            ? ($verificationStatus[self::VERIFICATION_OVERALL_STATUS_KEY] ?? null)
            : null;

        if (!is_string($clientId) || $clientId === ''
            || !is_string($scanRef) || $scanRef === ''
            || !is_string($overallStatus) || $overallStatus === ''
        ) {
            $this->logger->error(
                'Unable to process webhook, unable to retrieve clientId, scanRef and/or status',
                self::LOGGER_CONTEXT
            );
            return $this->result('ERROR');
        }

        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                sprintf('Unable to process webhook, no verification record for clientID %s found', $clientId),
                self::LOGGER_CONTEXT
            );
            return $this->result('ERROR');
        }

        // Only accept results for the verification session that is currently stored for this client. This rejects
        // stale webhooks from earlier attempts as well as forged requests that do not know the scan reference.
        if (!hash_equals($verification->getScanReference(), $scanRef)) {
            $this->logger->error(
                sprintf(
                    'Ignoring webhook for clientID %s: scanRef %s does not match the stored verification',
                    $clientId,
                    $scanRef
                ),
                self::LOGGER_CONTEXT
            );
            return $this->result('ERROR');
        }

        // A non-final result is preliminary and will be followed by a manual review webhook.
        // Webhooks that do not carry the flag (e.g. expired or cancelled sessions) are treated as final.
        $isFinal = !array_key_exists(self::VERIFICATION_FINAL_KEY, $requestData)
            || $requestData[self::VERIFICATION_FINAL_KEY] === true;

        if (!$isFinal) {
            $this->logger->notice(
                sprintf(
                    'Received preliminary status %s for clientID %s, waiting for the final result',
                    $overallStatus,
                    $clientId
                ),
                self::LOGGER_CONTEXT
            );

            // The customer finished the flow and a manual review follows. Only mark sessions that are still open,
            // so a late preliminary webhook can never overwrite a final result.
            if (!$verification->getIsVerified()
                && in_array($verification->getStatus(), [null, VerificationInterface::STATUS_PENDING], true)
            ) {
                $verification->setStatus(VerificationInterface::STATUS_REVIEWING);
                $this->saveVerification($verification, $clientId);
            }

            return $this->result('OK');
        }

        $isVerificationApproved = $overallStatus === self::VERIFICATION_SUCCESS_STATUS;
        $verification->setIsVerified($isVerificationApproved);
        $verification->setStatus($this->normaliseStatus($overallStatus));

        $this->saveVerification($verification, $clientId);

        $this->logger->notice(
            sprintf(
                'Processed webhook, updated verification status for clientID %s to %s (verified: %s)',
                $clientId,
                $overallStatus,
                $isVerificationApproved ? 'yes' : 'no'
            ),
            self::LOGGER_CONTEXT
        );

        return $this->result('OK');
    }

    /**
     * Reject the request when a signing key is configured and the Idenfy-Signature header does not match
     *
     * @param string $rawBody
     * @return void
     * @throws WebapiException
     */
    private function validateSignature(string $rawBody): void
    {
        $signingKey = $this->config->getWebhookSigningKey();

        if ($signingKey === null) {
            $this->logger->warning(
                'No webhook signing key is configured, so the authenticity of this webhook cannot be verified. '
                . 'Configure one under Stores > Configuration > Idenfy > API Configuration.',
                self::LOGGER_CONTEXT
            );
            return;
        }

        $signature = $this->request->getHeader(self::SIGNATURE_HEADER);
        $expectedSignature = hash_hmac('sha256', $rawBody, $signingKey);

        if (!is_string($signature) || !hash_equals($expectedSignature, strtolower(trim($signature)))) {
            $this->logger->error('Rejected webhook with a missing or invalid Idenfy-Signature header', self::LOGGER_CONTEXT);
            throw new WebapiException(__('Invalid signature.'), 0, WebapiException::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @param Verification $verification
     * @param string $clientId
     * @return void
     * @throws WebapiException
     */
    private function saveVerification(Verification $verification, string $clientId): void
    {
        try {
            $this->verificationRepository->save($verification);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf('Unable to store webhook result for clientID %s: %s', $clientId, $e->getMessage()),
                self::LOGGER_CONTEXT
            );
            // Let Idenfy retry the delivery
            throw new WebapiException(__('Unable to process the webhook.'), 0, WebapiException::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * Overall status as stored in the status column, e.g. APPROVED, DENIED, SUSPECTED or EXPIRED
     *
     * @param string $overallStatus
     * @return string
     */
    private function normaliseStatus(string $overallStatus): string
    {
        $status = substr((string) preg_replace('/[^A-Z_]/', '', strtoupper($overallStatus)), 0, 32);

        return $status !== '' ? $status : 'UNKNOWN';
    }

    /**
     * @param string $status
     * @return string[]
     */
    private function result(string $status): array
    {
        return ['status' => $status];
    }
}
