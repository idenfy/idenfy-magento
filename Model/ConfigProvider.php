<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Idenfy\CustomerVerification\Model\Action\GetVerificationStatus;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Exposes the verification state to the checkout as window.checkoutConfig.idenfy, so the verification modal does not
 * have to request it when the checkout loads
 */
class ConfigProvider implements ConfigProviderInterface
{
    /** @var GetVerificationStatus */
    private GetVerificationStatus $getVerificationStatus;

    /** @var Config */
    private Config $config;

    /**
     * @param GetVerificationStatus $getVerificationStatus
     * @param Config $config
     */
    public function __construct(
        GetVerificationStatus $getVerificationStatus,
        Config $config
    ) {
        $this->getVerificationStatus = $getVerificationStatus;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $isEnabled = $this->config->isEnabled();
        $status = $isEnabled ? $this->getVerificationStatus->execute() : GetVerificationStatus::NOT_REQUIRED;

        return [
            'idenfy' => [
                'isEnabled' => $isEnabled,
                'status' => $status,
                'needsVerification' => in_array($status, GetVerificationStatus::BLOCKING_STATUSES, true),
            ]
        ];
    }
}
