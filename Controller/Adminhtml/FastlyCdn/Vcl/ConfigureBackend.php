<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use \Fastly\Cdn\Model\Config;

class ConfigureBackend extends \Magento\Backend\App\Action
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var JsonFactory
     */
    protected $resultJson;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Vcl
     */
    protected $vcl;

    /**
     * @var Config
     */
    protected $config;


    /**
     * ConfigureBackend constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Vcl $vcl
     * @param Config $config
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Api $api,
        Vcl $vcl,
        Config $config
    )
    {
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
                'name' => $this->getRequest()->getParam('name'),
                'shield' => $this->getRequest()->getParam('shield'),
                'connect_timeout' => $this->getRequest()->getParam('connect_timeout'),
                'between_bytes_timeout' => $this->getRequest()->getParam('between_bytes_timeout'),
                'first_byte_timeout' => $this->getRequest()->getParam('first_byte_timeout'),
            ];
            $service = $this->api->checkServiceDetails();

            if(!$service) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Service details.'));
            }

            $currActiveVersion = $this->vcl->determineVersions($service->versions);

            if($currActiveVersion['active_version'] != $activeVersion) {
                return $result->setData(array('status' => false, 'msg' => 'Active versions mismatch.'));
            }

            $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

            if(!$clone) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to clone active version.'));
            }

            $configureBackend = $this->api->configureBackend($params, $clone->number, $oldName);

            if(!$configureBackend) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to update Backend configuration.'));
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                return $result->setData(array('status' => false, 'msg' => 'Failed to validate service version: '.$validate->msg));
            }

            if($activate_flag === 'true') {
                $this->api->activateVersion($clone->number);
            }
            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook('*Backend ' . $this->getRequest()->getParam('name') . ' has been changed in Fastly version ' . $clone->number . '*');
            }

            return $result->setData(array('status' => true, 'active_version' => $clone->number));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
