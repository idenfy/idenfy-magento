<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Controller\Verification;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Process implements HttpPostActionInterface
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

    /** @var JsonResultFactory */
    private JsonResultFactory $jsonResultFactory;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param Http $request
     * @param VerificationRepositoryInterface $verificationRepository
     * @param JsonResultFactory $jsonResultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Http $request,
        VerificationRepositoryInterface $verificationRepository,
        JsonResultFactory $jsonResultFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->verificationRepository = $verificationRepository;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $result->setHttpResponseCode(200);

        $clientId = $this->request->getPostValue(self::VERIFICATION_CLIENT_ID_KEY);
        $verificationStatus = $this->request->getPostValue(self::VERIFICATION_STATUS_KEY);

        if (!$clientId || !$verificationStatus || !isset($verificationStatus[self::VERIFICATION_OVERALL_STATUS_KEY])) {
            $this->logger->error(
                'Unable to process webhook, unable to retrieve clientID and/or status',
                self::LOGGER_CONTEXT
            );
            return $result;
        }

        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                sprintf('Unable to process webhook, no verification record for clientID %s found', $clientId),
                self::LOGGER_CONTEXT
            );
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
