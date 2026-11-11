<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Action;

use Idenfy\CustomerVerification\Model\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class NeedsVerification
{

    /** @var CheckoutSession  */
    private CheckoutSession $checkoutSession;

    /** @var GetClientId  */
    private GetClientId $getClientId;

    /** @var CheckCustomerVerification  */
    private CheckCustomerVerification $checkCustomerVerification;

    /** @var Config  */
    private Config $config;

    /**
     * @param GetClientId $getClientId
     * @param CheckCustomerVerification $checkCustomerVerification
     * @param CheckoutSession $checkoutSession
     * @param Config $config
     */
    public function __construct(
        GetClientId $getClientId,
        CheckCustomerVerification $checkCustomerVerification,
        CheckoutSession $checkoutSession,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClientId = $getClientId;
        $this->checkCustomerVerification = $checkCustomerVerification;
        $this->config = $config;
    }

    /**
     * Whether the customer of the current checkout session still has to verify with Idenfy
     *
     * @return bool
     */
    public function execute(): bool
    {
        if (!$this->config->isEnabled()) {
            return false;
        }

        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException|LocalizedException $e) {
            return false;
        }

        $clientId = $this->getClientId->execute($quote);
        if ($clientId === null) {
            return true;
        }

        return !$this->checkCustomerVerification->execute($clientId);
    }
}
