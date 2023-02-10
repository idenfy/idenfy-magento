<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Exception;
use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Api\Data\VerificationInterfaceFactory;
use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\ResourceModel\Verification as VerificationResource;
use Magento\Framework\Exception\NoSuchEntityException;

class VerificationRepository implements VerificationRepositoryInterface
{

    /** @var VerificationResource  */
    private VerificationResource $verificationResource;

    /** @var VerificationInterfaceFactory  */
    private VerificationInterfaceFactory $verificationFactory;

    /**
     * @param VerificationResource $verificationResource
     * @param VerificationInterfaceFactory $verificationFactory
     */
    public function __construct(
        VerificationResource $verificationResource,
        VerificationInterfaceFactory $verificationFactory
    ) {
        $this->verificationResource = $verificationResource;
        $this->verificationFactory = $verificationFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(Verification $verification): Verification
    {
        $this->verificationResource->save($verification);
        return $verification;
    }

    /**
     * @inheritdoc
     */
    public function delete(Verification $verification): bool
    {
        try {
            $this->verificationResource->delete($verification);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function get(int $verificationId): Verification
    {
        $verification = $this->verificationFactory->create();
        $this->verificationResource->load($verification, $verificationId);

        if (!$verification->getId()) {
            throw NoSuchEntityException::singleField(VerificationInterface::ENTITY_ID, $verificationId);
        }

        return $verification;
    }

    /**
     * @inheritdoc
     */
    public function getByCustomerId(int $customerId): Verification
    {
        $verification = $this->verificationFactory->create();
        $this->verificationResource->load(
            $verification,
            $customerId,
            VerificationInterface::CUSTOMER_ID
        );

        if (!$verification->getId()) {
            throw NoSuchEntityException::singleField(VerificationInterface::CUSTOMER_ID, $customerId);
        }

        return $verification;
    }

    /**
     * @inheritdoc
     */
    public function getByClientId(string $clientId): Verification
    {
        $verification = $this->verificationFactory->create();
        $this->verificationResource->load(
            $verification,
            $clientId,
            VerificationInterface::CLIENT_ID
        );

        if (!$verification->getId()) {
            throw NoSuchEntityException::singleField(VerificationInterface::CLIENT_ID, $clientId);
        }

        return $verification;
    }
}
