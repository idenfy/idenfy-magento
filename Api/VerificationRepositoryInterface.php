<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Api;

use Idenfy\CustomerVerification\Model\Verification;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;

interface VerificationRepositoryInterface
{
    /**
     * Store a verification record in the database
     *
     * @param Verification $verification
     * @return Verification
     * @throws AlreadyExistsException
     */
    public function save(Verification $verification): Verification;

    /**
     * Remove a verification record from the database
     * @param Verification $verification
     * @return bool
     */
    public function delete(Verification $verification): bool;

    /**
     * Retrieve a verification record from the database
     *
     * @param int $verificationId
     * @return Verification
     * @throws NoSuchEntityException
     */
    public function get(int $verificationId): Verification;

    /**
     * Retrieve a verification record from the database based on the customer ID
     *
     * @param int $customerId
     * @return Verification
     * @throws NoSuchEntityException
     */
    public function getByCustomerId(int $customerId): Verification;

    /**
     * Retrieve a verification record from the database based on the client ID
     *
     * @param string $clientId
     * @return Verification
     * @throws NoSuchEntityException
     */
    public function getByClientId(string $clientId): Verification;
}
