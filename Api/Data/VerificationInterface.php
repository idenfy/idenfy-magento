<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Api\Data;

interface VerificationInterface
{
    /**
     * String constants for property names
     */
    public const ENTITY_ID = 'entity_id';
    public const IS_VERIFIED = 'is_verified';
    public const CUSTOMER_ID = 'customer_id';
    public const AUTH_TOKEN = 'auth_token';
    public const SCAN_REFERENCE = 'scan_reference';
    public const CLIENT_ID = 'client_id';
    public const DIGIT_STRING = 'digit_string';
    public const VERIFICATION_DATA = 'verification_data';
    public const MESSAGE = 'message';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Statuses set by the module. Final results store the overall status received from Idenfy (APPROVED, DENIED,
     * SUSPECTED, EXPIRED, ...).
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REVIEWING = 'REVIEWING';
    public const STATUS_APPROVED = 'APPROVED';

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return void
     */
    public function setCustomerId(?int $customerId): void;

    /**
     * @return string
     */
    public function getAuthToken(): string;

    /**
     * @param string $authToken
     * @return void
     */
    public function setAuthToken(string $authToken): void;

    /**
     * @return string
     */
    public function getScanReference(): string;

    /**
     * @param string $scanReference
     * @return void
     */
    public function setScanReference(string $scanReference): void;

    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @param string $clientId
     * @return void
     */
    public function setClientId(string $clientId): void;

    /**
     * @return string|null
     */
    public function getDigitString(): ?string;

    /**
     * @param string|null $digitString
     * @return void
     */
    public function setDigitString(?string $digitString): void;

    /**
     * @return string
     */
    public function getVerificationData(): string;

    /**
     * @param string $verificationData
     * @return void
     */
    public function setVerificationData(string $verificationData): void;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void;

    /**
     * @return bool
     */
    public function getIsVerified(): bool;

    /**
     * @param bool $isVerified
     * @return void
     */
    public function setIsVerified(bool $isVerified): void;

    /**
     * PENDING, REVIEWING or the final overall status received from Idenfy. Null for records created before 1.2.0.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string|null $status
     * @return void
     */
    public function setStatus(?string $status): void;

    /**
     * Creation time in UTC (Y-m-d H:i:s)
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Last update time in UTC (Y-m-d H:i:s)
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
