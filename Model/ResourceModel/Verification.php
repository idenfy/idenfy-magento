<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\ResourceModel;

use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Verification extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'idenfy_verification_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('idenfy_verification', VerificationInterface::ENTITY_ID);
        $this->_useIsObjectNew = true;
    }
}
