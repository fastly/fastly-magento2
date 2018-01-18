<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Upload extends \Magento\Backend\App\Action
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
     * @var DateTime
     */
    protected $time;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Upload constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param DateTime $time
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        DateTime $time,
        TimezoneInterface $timezone
    )
    {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->time = $time;
        $this->timezone = $timezone;
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
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
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

            $snippets = $this->config->getVclSnippets();
            $ignoredUrlParameters = $this->config->getIgnoredUrlParameters();
            $adminPathTimeout = $this->config->getAdminPathTimeout();
            $adminUrl = $this->vcl->getAdminFrontName();

            $ignoredUrlParameterPieces = explode(",", $ignoredUrlParameters);
            $filterIgnoredUrlParameterPieces = array_filter(array_map('trim', $ignoredUrlParameterPieces));
            $queryParameters = implode('|', $filterIgnoredUrlParameterPieces);

            foreach($snippets as $key => $value)
            {
                $value = str_replace('####ADMIN_PATH####', $adminUrl, $value);
                $value = str_replace('####ADMIN_PATH_TIMEOUT####', $adminPathTimeout, $value);
                $value = str_replace('####QUERY_PARAMETERS####', $queryParameters, $value);
                $snippetData = array('name' => Config::FASTLY_MAGENTO_MODULE.'_'.$key, 'type' => $key, 'dynamic' => "0", 'priority' => 50, 'content' => $value);
                $status = $this->api->uploadSnippet($clone->number, $snippetData);

                if(!$status) {
                    return $result->setData(array('status' => false, 'msg' => 'Failed to upload the Snippet file.'));
                }
            }

            $condition = array('name' => Config::FASTLY_MAGENTO_MODULE.'_pass', 'statement' => 'req.http.x-pass', 'type' => 'REQUEST', 'priority' => 90);
            $createCondition = $this->api->createCondition($clone->number, $condition);

            if(!$createCondition) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create a REQUEST condition.'));
            }

            $request = array(
                'action' => 'pass',
                'max_stale_age' => 3600,
                'name' => Config::FASTLY_MAGENTO_MODULE.'_request',
                'request_condition' => $createCondition->name,
                'service_id' => $service->id,
                'version' => $currActiveVersion['active_version']
            );

            $createReq = $this->api->createRequest($clone->number, $request);

            if(!$createReq) {
                return $result->setData(array('status' => false, 'msg' => 'Failed to create a REQUEST object.'));
            }

            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                return $result->setData(array('status' => false, 'msg' => 'Failed to validate service version: '.$validate->msg));
            }

            if($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook('*Upload VCL has been initiated and activated in version ' . $clone->number . '*');
            }

            return $result->setData(array('status' => true, 'active_version' => $clone->number));
        } catch (\Exception $e) {
            return $result->setData(array('status' => false, 'msg' => $e->getMessage()));
        }
    }
}
