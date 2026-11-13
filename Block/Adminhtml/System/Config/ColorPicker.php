<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Hex colour field with a native colour picker next to it
 */
class ColorPicker extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setData('class', trim($element->getData('class') . ' idenfy-color-input'));
        $element->setData('placeholder', '#RRGGBB');

        $component = [
            'Idenfy_CustomerVerification/js/color-picker' => ['target' => '#' . $element->getHtmlId()],
        ];

        return parent::_getElementHtml($element)
            . sprintf(
                '<input type="color" class="idenfy-color-swatch" aria-label="%s" data-mage-init="%s"%s/>',
                $this->_escaper->escapeHtmlAttr(__('Pick a colour')),
                $this->_escaper->escapeHtmlAttr((string) json_encode($component, JSON_UNESCAPED_SLASHES)),
                $element->getData('disabled') ? ' disabled="disabled"' : ''
            );
    }
}
