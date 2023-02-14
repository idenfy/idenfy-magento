<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CheckCustomerVerification
{
    /** @var VerificationRepositoryInterface  */
    private VerificationRepositoryInterface $verificationRepository;

    /**
     * @param VerificationRepositoryInterface $verificationRepository
     */
    public function __construct(VerificationRepositoryInterface $verificationRepository)
    {
        $this->verificationRepository = $verificationRepository;
    }

    /**
     * @param string $clientId
     * @return bool
     */
    public function execute(string $clientId): bool
    {
        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return $verification->getIsVerified();
    }
}
