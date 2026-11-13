<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Model\ResourceModel\Verification as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class Verification extends AbstractModel implements VerificationInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'idenfy_verification_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $customerId = $this->getData(self::CUSTOMER_ID);
        return $customerId ? (int) $customerId : null;
    }

    /**
     * @param int|null $customerId
     * @return void
     */
    public function setCustomerId(?int $customerId): void
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @return string
     */
    public function getAuthToken(): string
    {
        return (string) $this->getData(self::AUTH_TOKEN);
    }

    /**
     * @param string|null $authToken
     * @return void
     */
    public function setAuthToken(?string $authToken): void
    {
        $this->setData(self::AUTH_TOKEN, $authToken);
    }

    /**
     * @return string
     */
    public function getScanReference(): string
    {
        return (string) $this->getData(self::SCAN_REFERENCE);
    }

    /**
     * @param string|null $scanReference
     * @return void
     */
    public function setScanReference(?string $scanReference): void
    {
        $this->setData(self::SCAN_REFERENCE, $scanReference);
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return (string) $this->getData(self::CLIENT_ID);
    }

    /**
     * @param string|null $clientId
     * @return void
     */
    public function setClientId(?string $clientId): void
    {
        $this->setData(self::CLIENT_ID, $clientId);
    }

    /**
     * @return string|null
     */
    public function getDigitString(): ?string
    {
        return $this->getData(self::DIGIT_STRING);
    }

    /**
     * @param string|null $digitString
     * @return void
     */
    public function setDigitString(?string $digitString): void
    {
        $this->setData(self::DIGIT_STRING, $digitString);
    }

    /**
     * @return string
     */
    public function getVerificationData(): string
    {
        return (string) $this->getData(self::VERIFICATION_DATA);
    }

    /**
     * @param string $verificationData
     * @return void
     */
    public function setVerificationData(string $verificationData): void
    {
        $this->setData(self::VERIFICATION_DATA, $verificationData);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return (string) $this->getData(self::MESSAGE);
    }

    /**
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->setData(self::MESSAGE, $message);
    }

    /**
     * @return bool
     */
    public function getIsVerified(): bool
    {
        return (bool) $this->getData(self::IS_VERIFIED);
    }

    /**
     * @param bool $isVerified
     * @return void
     */
    public function setIsVerified(bool $isVerified): void
    {
        $this->setData(self::IS_VERIFIED, $isVerified);
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        $status = $this->getData(self::STATUS);
        return $status === null || $status === '' ? null : (string) $status;
    }

    /**
     * @param string|null $status
     * @return void
     */
    public function setStatus(?string $status): void
    {
        $this->setData(self::STATUS, $status);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        $createdAt = $this->getData(self::CREATED_AT);
        return $createdAt ? (string) $createdAt : null;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        $updatedAt = $this->getData(self::UPDATED_AT);
        return $updatedAt ? (string) $updatedAt : null;
    }
}
