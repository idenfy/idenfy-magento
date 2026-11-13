<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Verification status of the customer, as shown on the storefront
 *
 * The status is based on the webhook results stored by ProcessVerification, never on what the browser reports.
 */
class GetVerificationStatus
{
    /** Verification is disabled or there is no checkout session */
    public const NOT_REQUIRED = 'not_required';
    /** The customer is verified and can place the order */
    public const VERIFIED = 'verified';
    /** The customer has not started a verification yet */
    public const NOT_STARTED = 'not_started';
    /** A verification session was created, but no result has been received yet */
    public const PENDING = 'pending';
    /** A preliminary result was received, the final result follows after a manual review */
    public const REVIEWING = 'reviewing';
    /** The last verification was not approved, the customer can try again */
    public const FAILED = 'failed';

    /**
     * Statuses that keep the customer from placing an order
     */
    public const BLOCKING_STATUSES = [self::NOT_STARTED, self::PENDING, self::REVIEWING, self::FAILED];

    /** @var CheckoutSession */
    private CheckoutSession $checkoutSession;

    /** @var GetClientId */
    private GetClientId $getClientId;

    /** @var VerificationRepositoryInterface */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var Config */
    private Config $config;

    /**
     * @param CheckoutSession $checkoutSession
     * @param GetClientId $getClientId
     * @param VerificationRepositoryInterface $verificationRepository
     * @param Config $config
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        GetClientId $getClientId,
        VerificationRepositoryInterface $verificationRepository,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClientId = $getClientId;
        $this->verificationRepository = $verificationRepository;
        $this->config = $config;
    }

    /**
     * Verification status of the customer of the current checkout session
     *
     * @return string
     */
    public function execute(): string
    {
        if (!$this->config->isEnabled()) {
            return self::NOT_REQUIRED;
        }

        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
            return self::NOT_REQUIRED;
        }

        return $this->getForQuote($quote);
    }

    /**
     * @param CartInterface $quote
     * @return string
     */
    public function getForQuote(CartInterface $quote): string
    {
        $clientId = $this->getClientId->execute($quote);

        if ($clientId === null) {
            return self::NOT_STARTED;
        }

        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return self::NOT_STARTED;
        }

        return $this->getForVerification($verification);
    }

    /**
     * @param VerificationInterface $verification
     * @return string
     */
    public function getForVerification(VerificationInterface $verification): string
    {
        if ($verification->getIsVerified()) {
            return self::VERIFIED;
        }

        switch ($verification->getStatus()) {
            case null:
                // Unverified record created before 1.2.0, its state is unknown
                return self::NOT_STARTED;
            case VerificationInterface::STATUS_PENDING:
                return self::PENDING;
            case VerificationInterface::STATUS_REVIEWING:
                return self::REVIEWING;
            default:
                return self::FAILED;
        }
    }
}
