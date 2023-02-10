<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Model\ResourceModel\Verification;

use Idenfy\CustomerVerification\Model\ResourceModel\Verification as ResourceModel;
use Idenfy\CustomerVerification\Model\Verification as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'idenfy_verification_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
