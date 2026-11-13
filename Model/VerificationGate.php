<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Idenfy\CustomerVerification\Model\Action\CheckCustomerVerification;
use Idenfy\CustomerVerification\Model\Action\GetClientId;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Decides on the server whether an order may be placed for a quote
 *
 * This is what actually enforces the verification: the storefront modal only guides the customer. It applies to
 * every checkout (Luma, one-step checkout extensions, express buttons, REST and GraphQL), except for orders created
 * by an admin user or a trusted integration.
 */
class VerificationGate
{
    /** @var Config */
    private Config $config;

    /** @var GetClientId */
    private GetClientId $getClientId;

    /** @var CheckCustomerVerification */
    private CheckCustomerVerification $checkCustomerVerification;

    /** @var State */
    private State $appState;

    /** @var UserContextInterface */
    private UserContextInterface $userContext;

    /**
     * @param Config $config
     * @param GetClientId $getClientId
     * @param CheckCustomerVerification $checkCustomerVerification
     * @param State $appState
     * @param UserContextInterface $userContext
     */
    public function __construct(
        Config $config,
        GetClientId $getClientId,
        CheckCustomerVerification $checkCustomerVerification,
        State $appState,
        UserContextInterface $userContext
    ) {
        $this->config = $config;
        $this->getClientId = $getClientId;
        $this->checkCustomerVerification = $checkCustomerVerification;
        $this->appState = $appState;
        $this->userContext = $userContext;
    }

    /**
     * Whether the customer of this quote still has to verify before placing the order
     *
     * @param CartInterface $quote
     * @return bool
     */
    public function isBlocked(CartInterface $quote): bool
    {
        if (!$this->config->isEnabled() || $this->isTrustedContext()) {
            return false;
        }

        $clientId = $this->getClientId->execute($quote);

        if ($clientId === null) {
            return true;
        }

        return !$this->checkCustomerVerification->execute($clientId);
    }

    /**
     * Orders created in the admin panel, or through the API with an admin or integration token, are not subject to
     * customer verification
     *
     * @return bool
     */
    private function isTrustedContext(): bool
    {
        try {
            if ($this->appState->getAreaCode() === Area::AREA_ADMINHTML) {
                return true;
            }
        } catch (LocalizedException $e) {
            // No area set (e.g. CLI): fall through and treat as untrusted
        }

        return in_array(
            (int) $this->userContext->getUserType(),
            [UserContextInterface::USER_TYPE_ADMIN, UserContextInterface::USER_TYPE_INTEGRATION],
            true
        );
    }
}
