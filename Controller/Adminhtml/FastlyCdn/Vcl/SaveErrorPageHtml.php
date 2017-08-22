<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class SaveErrorPageHtml extends \Magento\Backend\App\Action
{
    /**
     * VCL error snippet path
     */
    const VCL_ERROR_SNIPPET_PATH = '/vcl_snippets_error_page';
    const VCL_ERROR_SNIPPET = 'deliver.vcl';

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
     * SaveErrorPageHtml constructor.
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
     * Save Error Page Html
     *
     * @return $resultJsonFactory
     */
    public function execute()
    {
        try {
            $result = $this->resultJson->create();
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $html = $this->getRequest()->getParam('html');
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

            $snippets = $this->config->getVclSnippets(self::VCL_ERROR_SNIPPET_PATH, self::VCL_ERROR_SNIPPET);


            foreach($snippets as $key => $value)
            {
                $snippetData = array('name' => Config::FASTLY_MAGENTO_MODULE.'_error_page_'.$key, 'type' => $key, 'dynamic' => "0", 'content' => $value);
                $status = $this->api->uploadSnippet($clone->number, $snippetData);

                if(!$status) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to upload the Snippet file.'));
                }
            }

            $condition = array(
                'name' => Config::FASTLY_MAGENTO_MODULE.'_error_page_condition',
                'statement' => 'req.http.ResponseObject == "970"',
                'type' => 'REQUEST',
            );

            $createCondition = $this->api->createCondition($clone->number, $condition);

            if(!$createCondition) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create a RESPONSE condition.'));
            }

            $response = array(
                'name' => Config::ERROR_PAGE_RESPONSE_OBJECT,
                'request_condition' => $createCondition->name,
                'content'   =>  $html,
                'status' => "503",
                'response' => "Service Temporarily Unavailable"
            );

            $createResponse = $this->api->createResponse($clone->number, $response);

            if(!$createResponse) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create a RESPONSE object.'));
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                return $result->setData(array('status' => false, 'msg' => 'Failed to validate service version: '.$validate->msg));
            }

            if($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook('*New Error/Maintenance page has updated and activated under config version ' . $clone->number . '*');
            }

            return $result->setData(array('status' => true, 'active_version' => $clone->number));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
