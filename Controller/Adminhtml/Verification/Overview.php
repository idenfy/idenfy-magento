<?php

namespace Idenfy\CustomerVerification\Controller\Adminhtml\Verification;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;

class Overview extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Idenfy_CustomerVerification::listing';

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\View\Result\Page|(\Magento\Framework\View\Result\Page&ResultInterface)|ResultInterface
     */
    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
