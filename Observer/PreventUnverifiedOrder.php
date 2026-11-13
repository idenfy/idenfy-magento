<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Observer;

use Idenfy\CustomerVerification\Model\VerificationGate;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;

/**
 * Rejects orders of customers who have not completed the verification
 *
 * This is what enforces the verification: it runs right before every order is created from a quote, so it covers
 * the checkout, one-step checkout extensions, express buttons and direct REST or GraphQL calls alike. The storefront
 * reopens the verification modal when it receives this error.
 */
class PreventUnverifiedOrder implements ObserverInterface
{
    /** @var VerificationGate */
    private VerificationGate $verificationGate;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param VerificationGate $verificationGate
     * @param LoggerInterface $logger
     */
    public function __construct(VerificationGate $verificationGate, LoggerInterface $logger)
    {
        $this->verificationGate = $verificationGate;
        $this->logger = $logger;
    }

    /**
     * Error shown to the customer, also used by the storefront to recognise the rejection
     *
     * @return Phrase
     */
    public static function getMessage(): Phrase
    {
        return __('Please verify your identity before placing your order.');
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getData('quote');

        if (!$quote instanceof CartInterface || !$this->verificationGate->isBlocked($quote)) {
            return;
        }

        $this->logger->warning(
            sprintf('Rejected order for quote %s: the customer has not completed identity verification.', $quote->getId())
        );

        throw new LocalizedException(self::getMessage());
    }
}
