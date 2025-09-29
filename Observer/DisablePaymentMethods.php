<?php
declare(strict_types=1);

namespace idenfy\CustomerVerification\Observer;

use Idenfy\CustomerVerification\Api\VerificationRepositoryInterface;
use Idenfy\CustomerVerification\Model\Action\GetClientId;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Disables payment methods if the customer is not verified yet.
 */
class DisablePaymentMethods implements ObserverInterface
{
    /** @var GetClientId */
    private GetClientId $getClientId;

    /** @var VerificationRepositoryInterface */
    private VerificationRepositoryInterface $verificationRepository;

    /** @var CheckoutSession  */
    private CheckoutSession $checkoutSession;

    public function __construct(
        GetClientId $getClientId,
        VerificationRepositoryInterface $verificationRepository,
        CheckoutSession $checkoutSession
    ) {
        $this->getClientId = $getClientId;
        $this->verificationRepository = $verificationRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Execute Observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->isCustomerVerified()) {
            $result = $observer->getEvent()->getResult();
            $result->setIsAvailable(false);
        }
    }

    /**
     * @return bool
     */
    private function isCustomerVerified(): bool
    {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
            return true;
        }

        $clientId = $this->getClientId->execute($quote);

        try {
            $verification = $this->verificationRepository->getByClientId($clientId);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        return $verification->getIsVerified();
    }
}
