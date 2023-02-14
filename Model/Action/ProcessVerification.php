<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class ProcessVerification
{
    public const VERIFICATION_STATUS_KEY = 'status';
    public const VERIFICATION_OVERALL_STATUS_KEY = 'overall';
    public const VERIFICATION_CLIENT_ID_KEY = 'clientId';
    public const VERIFICATION_SUCCESS_STATUS = 'APPROVED';
    public const LOGGER_CONTEXT = ['source' => 'webhook'];

    /** @var Http */
    private Http $request;

    /** @var VerificationRepositoryInterface */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var Json */
    private Json $jsonSerializer;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param Http $request
     * @param VerificationRepositoryInterface $verificationRepository
     * @param Json $jsonSerializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        Http $request,
        VerificationRepositoryInterface $verificationRepository,
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->verificationRepository = $verificationRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * @return string[]
     * @throws AlreadyExistsException
     */
    public function execute(): array
    {
        $result = ['status' => 'OK'];

        $requestData = $this->jsonSerializer->unserialize($this->request->getContent());

        $clientId = $requestData[self::VERIFICATION_CLIENT_ID_KEY] ?? null;
        $verificationStatus = $requestData[self::VERIFICATION_STATUS_KEY] ?? null;

        if (!$clientId || !$verificationStatus || !isset($verificationStatus[self::VERIFICATION_OVERALL_STATUS_KEY])) {
            $this->logger->error(
                'Unable to process webhook, unable to retrieve clientID and/or status',
                self::LOGGER_CONTEXT
            );
            $result['status'] = 'ERROR';
            return $result;
        }

        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                sprintf('Unable to process webhook, no verification record for clientID %s found', $clientId),
                self::LOGGER_CONTEXT
            );
            $result['status'] = 'ERROR';
            return $result;
        }

        $isVerificationApproved = $verificationStatus[self::VERIFICATION_OVERALL_STATUS_KEY] === self::VERIFICATION_SUCCESS_STATUS;
        if ($isVerificationApproved) {
            $verification->setIsVerified(true);
        } else {
            $verification->setIsVerified(false);
        }

        $this->verificationRepository->save($verification);

        $this->logger->notice(
            sprintf(
                'Processed webhook, updated verification status for clientID %s to status %s',
                $clientId,
                $isVerificationApproved
            ),
            ['source' => 'webhook']
        );

        return $result;
    }
}
