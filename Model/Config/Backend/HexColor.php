<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\Config\Backend;

use Idenfy\CustomerVerification\Model\Config;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

/**
 * Only accepts hex colours, so that the value can be safely used as a CSS colour on the storefront
 */
class HexColor extends Value
{
    /**
     * @return $this
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = trim((string) $this->getValue());

        if ($value !== '' && !preg_match(Config::HEX_COLOR_PATTERN, $value)) {
            throw new ValidatorException(
                __('"%1" is not a valid hex colour. Use a value such as #1979C3.', $value)
            );
        }

        $this->setValue(strtoupper($value));

        return parent::beforeSave();
    }
}
