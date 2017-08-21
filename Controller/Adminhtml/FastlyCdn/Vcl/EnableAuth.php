<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class EnableAuth extends \Magento\Backend\App\Action
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
     * @var Config
     */
    protected $config;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Vcl
     */
    protected $vcl;

    /**
     * ForceTls constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    )
    {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        parent::__construct($context);
    }

    /**
     * Upload Auth VCL snippets
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $enabled = false;

            if(!$service) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to check Service details.'));
            }

            $currActiveVersion = $this->vcl->determineVersions($service->versions);

            if($currActiveVersion['active_version'] != $activeVersion) {
                return $result->setData(array('status' => false, 'msg' => 'Active versions mismatch.'));
            }

            $vclPath = \Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl\CheckAuthSetting::VCL_AUTH_SNIPPET_PATH;
            $snippets = $this->config->getVclSnippets($vclPath);

            // Check if snippets exist
            $status = true;
            foreach($snippets as $key => $value)
            {
                $name = Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key;
                $status = $this->api->getSnippet($activeVersion, $name);

                if(!$status) {
                    break;
                }
            }

            if(!$status)
            {
                // Check if Auth has entries
                $dictionary = $this->api->getSingleDictionary($activeVersion, 'magentomodule_basic_auth');

                // Fetch Authentication items
                if((is_array($dictionary) && empty($dictionary)) || !isset($dictionary->id))
                {
                    return $result->setData(array('status' => 'empty', 'msg' => 'You must add users in order to enable Basic Authentication.'));
                }

                $authItems = $this->api->dictionaryItemsList($dictionary->id);

                if(is_array($authItems) && empty($authItems))
                {
                    return $result->setData(array('status' => 'empty', 'msg' => 'You must add users in order to enable Basic Authentication.'));
                }

                $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

                if(!$clone) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to clone active version.'));
                }

                // Insert snippet
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

                $enabled = true;
            } else {

                $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

                if(!$clone) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to clone active version.'));
                }

                // Remove snippets
                foreach($snippets as $key => $value)
                {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_basic_auth_'.$key;
                    $status = $this->api->removeSnippet($clone->number, $name);
                }
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                return $result->setData(array('status' => false, 'msg' => 'Failed to validate service version: '.$validate->msg));
            }

            if($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                if(!$enabled) {
                    $this->api->sendWebHook('*Basic Authentication has been turned OFF in Fastly version '. $clone->number . '*');
                } else {
                    $this->api->sendWebHook('*Basic Authentication has been turned ON in Fastly version '. $clone->number . '*');
                }
            }

            return $result->setData(array('status' => true));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
