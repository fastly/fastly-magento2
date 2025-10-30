<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckHttp3Status extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var Api
     */
    private $api;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        Context $context,
        Api $api,
        JsonFactory $resultJsonFactory
    ) {
        $this->api = $api;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $req = $this->api->hasSnippet($activeVersion, Config::ENABLE_HTTP3_SETTING_NAME);

            if (!$req) {
                return $result->setData(['status' => false]);
            }

            return $result->setData([
                'status'        => true,
                'req_setting'   => $req
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
