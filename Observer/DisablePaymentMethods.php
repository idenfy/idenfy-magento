<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Observer;

use Idenfy\CustomerVerification\Model\Action\CheckCustomerVerification;
use Idenfy\CustomerVerification\Model\Action\GetClientId;
use Idenfy\CustomerVerification\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Disables payment methods if the customer is not verified yet.
 */
class DisablePaymentMethods implements ObserverInterface
{
    /** @var GetClientId */
    private GetClientId $getClientId;

    /** @var CheckCustomerVerification */
    private CheckCustomerVerification $checkCustomerVerification;

    /** @var Config */
    private Config $config;

    /** @var State */
    private State $appState;

    /**
     * @param GetClientId $getClientId
     * @param CheckCustomerVerification $checkCustomerVerification
     * @param Config $config
     * @param State $appState
     */
    public function __construct(
        GetClientId $getClientId,
        CheckCustomerVerification $checkCustomerVerification,
        Config $config,
        State $appState
    ) {
        $this->getClientId = $getClientId;
        $this->checkCustomerVerification = $checkCustomerVerification;
        $this->config = $config;
        $this->appState = $appState;
    }

    /**
     * Execute Observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $result = $event->getResult();

        if (!$result || !$result->getData('is_available')) {
            return;
        }

        // Orders created by an admin are not subject to customer verification
        if ($this->isAdminArea()) {
            return;
        }

        // Use the quote the payment method is evaluated for, not the session quote, so that API and
        // multi-quote contexts are handled correctly. Without a quote there is nothing to verify.
        $quote = $event->getQuote();
        if (!$quote instanceof CartInterface) {
            return;
        }

        if (!$this->config->isEnabled()) {
            return;
        }

        if (!$this->isCustomerVerified($quote)) {
            $result->setData('is_available', false);
        }
    }

    /**
     * @param CartInterface $quote
     * @return bool
     */
    private function isCustomerVerified(CartInterface $quote): bool
    {
        $clientId = $this->getClientId->execute($quote);

        if ($clientId === null) {
            return false;
        }

        return $this->checkCustomerVerification->execute($clientId);
    }

    /**
     * @return bool
     */
    private function isAdminArea(): bool
    {
        try {
            return $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        } catch (LocalizedException $e) {
            return false;
        }
    }
}
