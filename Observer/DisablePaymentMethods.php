<?php
declare(strict_types=1);

namespace idenfy\CustomerVerification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Disables payment methods if the customer is not verified yet.
 */
class DisablePaymentMethods  implements ObserverInterface
{
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
        return false;
    }
}
