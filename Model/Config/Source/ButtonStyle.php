<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Config\Source;

use Idenfy\CustomerVerification\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

class ButtonStyle implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Config::BUTTON_STYLE_THEME, 'label' => __('Theme default')],
            ['value' => Config::BUTTON_STYLE_CUSTOM, 'label' => __('Custom colours')],
        ];
    }
}
