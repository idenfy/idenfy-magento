<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Magento\Quote\Api\Data\CartInterface;

class GetClientId
{

    /**
     * Build the Idenfy client ID for a quote
     *
     * Logged-in customers are identified by their customer ID, guests by their quote ID. Returns null when the
     * quote cannot be identified yet (a guest quote that has not been saved), so that unrelated visitors never
     * share the same client ID.
     *
     * @param CartInterface $quote
     * @return string|null
     */
    public function execute(CartInterface $quote): ?string
    {
        $customerId = (int) $quote->getCustomerId();
        if ($customerId > 0 && !$quote->getCustomerIsGuest()) {
            return (string) $customerId;
        }

        $quoteId = (int) $quote->getId();
        if ($quoteId > 0) {
            return 'guest-' . $quoteId;
        }

        return null;
    }
}
