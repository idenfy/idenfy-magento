<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Idenfy\CustomerVerification\Model\Action\NeedsVerification;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var NeedsVerification */
    private NeedsVerification $needsVerification;

    /** @var Config */
    private Config $config;

    public function __construct(
        NeedsVerification $needsVerification,
        Config $config
    ) {
        $this->needsVerification = $needsVerification;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $config = [];

        $config['idenfy'] = [
            'isEnabled' => $this->config->isEnabled(),
            'needsVerification' => $this->needsVerification->execute()
        ];

        return $config;
    }
}
