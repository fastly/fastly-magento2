<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\AdminTimeout;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class GetAdminUrl extends Action
{
    /**
     * @var UrlInterface
     */
    private $backendUrl;

    public function __construct(
        Context $context,
        UrlInterface $backendUrl
    ) {
        parent::__construct($context);
        $this->backendUrl = $backendUrl;
    }

    public function execute()
    {
        $admin = $this->backendUrl->getAreaFrontName();
        return;
    }
}
