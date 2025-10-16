<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model;

use Idenfy\CustomerVerification\Model\Action\NeedsVerification;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /** @var NeedsVerification */
    private NeedsVerification $needsVerification;

    public function __construct(NeedsVerification $needsVerification)
    {
        $this->needsVerification = $needsVerification;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'idenfy' => [
                'needsVerification' => $this->needsVerification->execute()
            ]
        ];
    }
}
