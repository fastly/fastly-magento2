<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Exception\LocalizedException;

class PushImageSettings extends Action
{
    /**
     * VCL snippet names
     */
    const CONDITION_NAME = 'fastly-image-optimizer-condition';
    const HEADER_NAME = 'fastly-image-optimizer-header';

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
     * @var bool Flag for uploading custom condition to VCL
     */
    protected $hasCondition = false;

    /**
     * @var bool Flag for uploading custom header to VCL
     */
    protected $hasHeader = false;

    /**
     * PushImageSettings constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request      = $request;
        $this->resultJson   = $resultJsonFactory;
        $this->config       = $config;
        $this->api          = $api;
        $this->vcl          = $vcl;

        parent::__construct($context);
    }

    /**
     * Upload VCL snippets for image optimizations
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        $activeVersion = $this->getRequest()->getParam('active_version');
        $checkOnly = $this->getRequest()->getParam('check_only');
        $activateVcl = $this->getRequest()->getParam('activate_flag');

        try {
            // Check if service has been initialized
            $service = $this->api->checkServiceDetails();
            if($service === false) {
                throw new LocalizedException(__('Failed to check Service details.'));
            }

            // Get the current version
            $currActiveVersion = $this->vcl->determineVersions($service->versions);
            if($currActiveVersion['active_version'] != $activeVersion) {
                throw new LocalizedException(__('Active versions mismatch.'));
            }

            // Check the status of image optimization configuration
            $this->hasCondition = $this->conditionExists($currActiveVersion['active_version']);
            $this->hasHeader = $this->headerExists($currActiveVersion['active_version']);
            if ($this->hasCondition === true && $this->hasHeader == true) {
                $result->setData([
                    'status'        => true,
                    'old_config'    => true
                ]);

                return $result;
            }

            // Is this check only request?
            if ($checkOnly == true) {
                $result->setData(['status' => true]);

                return $result;
            }

            // Lets clone it and push the required config
            $clone = $this->api->cloneVersion($currActiveVersion['active_version']);
            if($clone === false) {
                throw new LocalizedException(__('Failed to clone active version.'));
            }

            $this->createCondition($clone->number);
            $this->createHeader($clone->number);

            // Validate before sending success
            $validate = $this->api->validateServiceVersion($clone->number);

            if($validate->status == 'error') {
                throw new LocalizedException(__('Failed to validate service version: ' . $validate->msg));
           }

            if($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }
        } catch (\Exception $e) {
            $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);

            return $result;
        }

        $result->setData(['status' => true]);

        return $result;
    }

    /**
     * Determines if image optimization condition exists
     *
     * @param $version
     * @return bool
     */
    private function conditionExists($version)
    {
        $condition = $this->api->getCondition($version, self::CONDITION_NAME);

        if ($condition === false) {
            return false;
        }

        return true;
    }

    /**
     * Creates condition for configuring image optimization
     *
     * @param string $version
     * @return bool
     * @throws LocalizedException
     */
    private function createCondition($version)
    {
        if ($this->hasCondition === true) {
            return true;
        }

        $conditionData = [
            'name'      => self::CONDITION_NAME,
            'statement' => 'req.url.ext ~ "(?i)^(gif|png|jpg|jpeg|webp)$"',
            'priority'  => 10,
            'type'      => 'request'
        ];

        $response = $this->api->createCondition($version, $conditionData);

        if(!$response) {
            throw new LocalizedException(__('Failed to create the CONDITION.'));
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook(
                '*Image optimization CONDITION has been created in Fastly version '. $version . '*'
            );
        }

        return true;
    }

    /**
     * Determines if image optimization header exists
     *
     * @param $version
     * @return bool
     */
    private function headerExists($version)
    {
        $header = $this->api->getHeader($version, self::HEADER_NAME);

        if ($header === false) {
            return false;
        }

        return true;
    }

    /**
     * Creates header for configuring image optimization
     *
     * @param string $version
     * @return bool
     * @throws LocalizedException
     */
    private function createHeader($version)
    {
        if ($this->hasHeader === true) {
            return true;
        }

        $headerData = [
            'name'              => self::HEADER_NAME,
            'type'              => 'request',
            'action'            => 'set',
            'dst'               => 'http.x-fastly-imageopto-api',
            'src'               => '"fastly"',
            'ignore_if_set'     => 0,
            'priority'          => 1,
            'request_condition' => self::CONDITION_NAME
        ];

        $response = $this->api->createHeader($version, $headerData);

        if(!$response) {
            throw new LocalizedException(__('Failed to create the HEADER.'));
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook(
                '*Image optimization HEADER has been created in Fastly version '. $version . '*'
            );
        }
    }
}
