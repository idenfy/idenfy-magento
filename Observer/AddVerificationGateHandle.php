<?php
declare(strict_types=1);

namespace Idenfy\CustomerVerification\Observer;

use Idenfy\CustomerVerification\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;

/**
 * Adds the verification modal to the additional checkout pages configured in the admin panel
 *
 * One-step checkout extensions often run on their own page, where the layout of the default checkout is not loaded.
 * The default checkout itself includes the handle through checkout_index_index.xml.
 */
class AddVerificationGateHandle implements ObserverInterface
{
    public const HANDLE = 'idenfy_verification_gate';

    /** @var Config */
    private Config $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $fullActionName = strtolower((string) $observer->getData('full_action_name'));
        $layout = $observer->getData('layout');

        if ($fullActionName === ''
            || !$layout instanceof LayoutInterface
            || !in_array($fullActionName, $this->config->getAdditionalCheckoutPages(), true)
            || !$this->config->isEnabled()
        ) {
            return;
        }

        $layout->getUpdate()->addHandle(self::HANDLE);
    }
}
