<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config;

class ConfigureBackend extends Action
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * @var Config
     */
    private $config;

    /**
     * ConfigureBackend constructor
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Vcl $vcl
     * @param Config $config
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Api $api,
        Vcl $vcl,
        Config $config
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Upload VCL snippets
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        try {
            $result = $this->resultJson->create();
            $activate_flag = $this->getRequest()->getParam('activate_flag');
            $activeVersion = $this->getRequest()->getParam('active_version');
            $oldName = $this->getRequest()->getParam('name');
            $params = [
                'name'                  => $this->getRequest()->getParam('name'),
                'shield'                => $this->getRequest()->getParam('shield'),
                'connect_timeout'       => $this->getRequest()->getParam('connect_timeout'),
                'between_bytes_timeout' => $this->getRequest()->getParam('between_bytes_timeout'),
                'first_byte_timeout'    => $this->getRequest()->getParam('first_byte_timeout'),
            ];
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $configureBackend = $this->api->configureBackend($params, $clone->number, $oldName);

            if (!$configureBackend) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to update Backend configuration.'
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activate_flag === 'true') {
                $this->api->activateVersion($clone->number);
            }
            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Backend '
                    . $this->getRequest()->getParam('name')
                    . ' has been changed in Fastly version '
                    . $clone->number
                    . '*'
                );
            }

            return $result->setData([
                'status'            => true,
                'active_version'    => $clone->number
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
