<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Magento\Quote\Api\Data\CartInterface;

class GetClientId
{

    /**
     * @param CartInterface $quote
     * @return string
     */
    public function execute(CartInterface $quote): string
    {
        if (!$quote->getCustomerIsGuest()) {
            return (string) $quote->getCustomerId();
        }

        return 'guest-' . $quote->getEntityId();
    }
}
