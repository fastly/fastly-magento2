<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Auth;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class Enable extends \Magento\Backend\App\Action
{

    /**
     * Path to Authentication snippet
     */
    const VCL_AUTH_SNIPPET_PATH = '/vcl_snippets_basic_auth';

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
     * @var Config
     */
    protected $config;

    /**
     * @var Vcl
     */
    protected $vcl;

    /**
     * Auth Dictionary existance status
     */
    protected $dictionaryExists = false;

    /**
     * Current active version
     */
    protected $activeVersion;


    protected $resourceConfig;

    /**
     * ListAll constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface  $resourceConfig
    )
    {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->resourceConfig = $resourceConfig;
        parent::__construct($context);
    }

    /**
     * Get all dictionaries for active version
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $this->activeVersion = $this->getRequest()->getParam('active_version');
        $status = $this->getRequest()->getParam('status');

        if(!(int)$status)
        {
            return $this->createAuth();
        } else {
            return $this->deleteAuth();
        }
    }

    /**
     * Create Authentication entry
     *
     * @return $resultJsonFactory
     */
    public function createAuth()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->activeVersion;
            $service = $this->api->checkServiceDetails();

            if(!$service) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Service details.'));
            }

            $currActiveVersion = $this->vcl->determineVersions($service->versions);

            if($currActiveVersion['active_version'] != $activeVersion) {
                return $result->setData(array('status' => false, 'msg' => 'Active versions mismatch.'));
            }

            $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

            // Insert snippet
            $snippets = $this->config->getVclSnippets(self::VCL_AUTH_SNIPPET_PATH);

            foreach($snippets as $key => $value)
            {
                $snippetData = array(
                    'name' => Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key,
                    'type' => $key,
                    'dynamic' => "0",
                    'content' => $value,
                    'priority' => 40
                );
                $status = $this->api->uploadSnippet($clone->number, $snippetData);

                if(!$status) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to upload the Snippet file.'));
                }
            }

            // Create Auth Dictionary if needed
            $dictionary = $this->api->getSingleDictionary($activeVersion, 'magentomodule_basic_auth');

            // Fetch Authentication items
            if((is_array($dictionary) && empty($dictionary)) || $dictionary == false)
            {
                $params = ['name' => 'magentomodule_basic_auth'];
                $createDictionary = $this->api->createDictionary($clone->number, $params);

                if(!$createDictionary) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to create Dictionary container.'));
                }
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                return $result->setData(array('status' => false, 'msg' => 'Failed to validate service version: '.$validate->msg));
            }

            // Activate AUTH
            if($this->api->activateVersion($clone->number)) {
                $this->activeVersion = $clone->number;
            }

            $this->toggleBasicAuth(1);
            return $result->setData(array('status' => true, 'active_version' => $clone->number, 'msg_btn' => 'Click to Disable', 'msg' => 'Enabled'));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }


    /**
     * Delete Basic Auth related snippets
     *
     * @return $resultJsonFactory
     */
    public function deleteAuth()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->activeVersion;
            $service = $this->api->checkServiceDetails();

            if(!$service) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Service details.'));
            }

            $currActiveVersion = $this->vcl->determineVersions($service->versions);

            if($currActiveVersion['active_version'] != $activeVersion) {
                return $result->setData(array('status' => false, 'msg' => 'Active versions mismatch.'));
            }

            $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

            // Insert snippet
            $snippets = $this->config->getVclSnippets(self::VCL_AUTH_SNIPPET_PATH);

            foreach($snippets as $key => $value)
            {
                $name = Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key;
                $status = $this->api->removeSnippet($clone->number, $name);

                //if(!$status) {
                    //return $result->setData(array('status' => false, 'msg' => 'Failed to delete the Snippet file.'));
                //}
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                return $result->setData(array('status' => false, 'msg' => 'Failed to validate service version: '.$validate->msg));
            }

            // Activate AUTH
            if($this->api->activateVersion($clone->number)) {
                $this->activeVersion = $clone->number;
            }

            $this->toggleBasicAuth(0);
            return $result->setData(array('status' => true, 'active_version' => $clone->number, 'msg_btn' => 'Click to Enable', 'msg' => 'Disabled'));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }

    /**
     * Toggle basic Authentication on/off
     * @param $status bool
     */
    public function toggleBasicAuth($status)
    {
        $this->resourceConfig->saveConfig(
            \Fastly\Cdn\Model\Config::FASTLY_BASIC_AUTH_ENABLE,
            $status,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
    }
}