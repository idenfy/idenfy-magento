<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Config\Source;

use Idenfy\CustomerVerification\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class IntegrationMode implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::MODE_IFRAME, 'label' => __('Modal with embedded iFrame (recommended)')],
            ['value' => Config::MODE_REDIRECT, 'label' => __('Redirect to Idenfy')],
        ];
    }
}
