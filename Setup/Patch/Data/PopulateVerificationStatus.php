<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Setup\Patch\Data;

use Idenfy\CustomerVerification\Api\Data\VerificationInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Sets the status of records that were verified before the status column existed
 */
class PopulateVerificationStatus implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->update(
            $this->moduleDataSetup->getTable('idenfy_verification'),
            [VerificationInterface::STATUS => VerificationInterface::STATUS_APPROVED],
            [
                VerificationInterface::IS_VERIFIED . ' = ?' => 1,
                VerificationInterface::STATUS . ' IS NULL',
            ]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
