<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced;

use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class ToggleHttp3 extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var JsonFactory
     */
    private $resultJson;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * Upload VCL snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);

            $http3IsEnabled = $this->api->hasSnippet($activeVersion, Config::ENABLE_HTTP3_SETTING_NAME);
            $http3Snippets = $this->config->getVclSnippets(Config::HTTP3_PATH);

            if (!$http3IsEnabled) {

                // Add HTTP/3 snippet
                foreach ($http3Snippets as $key => $value) {
                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE . '_enable_http3_' . $key,
                        'type'      => $key,
                        'dynamic'   => "0",
                        'priority'  => 10,
                        'content'   => $value
                    ];
                    $this->api->uploadSnippet($clone->number, $snippetData);
                }
            } else {

                // Remove HTTP/3 snippet
                foreach ($http3Snippets as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_enable_http3_'.$key;
                    $this->api->removeSnippet($clone->number, $name);
                }
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            return $result->setData(['status' => true]);

        } catch (\Throwable $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

}
