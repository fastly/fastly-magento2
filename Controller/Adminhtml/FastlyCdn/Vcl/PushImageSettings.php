<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\ManagerFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Exception\LocalizedException;

class PushImageSettings extends Action
{
    /**
     * VCL snippet names
     */
    const CONDITION_NAME    = 'fastly-image-optimizer-condition';
    const HEADER_NAME       = 'fastly-image-optimizer-header';
    const VCL_SNIPPET_PATH  = '/vcl_snippets_image_optimizations';

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
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var ManagerFactory
     */
    protected $cacheFactory;
    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Vcl
     */
    protected $vcl;

    /**
     * @var int Current Fastly version
     */
    protected $currentVersion;

    /**
     * PushImageSettings constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param WriterInterface $configWriter
     * @param ManagerFactory $cacheManagerFactory
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        WriterInterface $configWriter,
        ManagerFactory $cacheManagerFactory,
        Api $api,
        Vcl $vcl
    ) {
        $this->request      = $request;
        $this->resultJson   = $resultJsonFactory;
        $this->config       = $config;
        $this->configWriter = $configWriter;
        $this->cacheFactory = $cacheManagerFactory;
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
        $checkOnly = $this->getRequest()->getParam('check_only');
        $status = $this->config->isImageOptimizationEnabled();

        if ($checkOnly == true) {
            $result->setData([
                'status'        => true,
                'setting_value' => $status
            ]);

            return $result;
        }

        $this->currentVersion = $this->getRequest()->getParam('active_version');
        $activateVcl = $this->getRequest()->getParam('activate_flag');

        try {
            // Check status of config
            $this->validateRequest();

            // Adjust snippet
            if ($status == true) {
                $this->removeSnippets($activateVcl);
                $status = false;
            } else {
                $this->pushSnippets($activateVcl);
                $status = true;
            }
        } catch (\Exception $e) {
            $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);

            return $result;
        }

        $this->setStatus($status);
        $result->setData([
            'status'    => true,
            'new_state' => $status
        ]);

        return $result;
    }

    /**
     * Push image optimiaztion related snippets
     *
     * @param $activateVcl
     * @throws LocalizedException
     */
    protected function pushSnippets($activateVcl)
    {
        // Lets clone it and push the required config
        $clone = $this->api->cloneVersion($this->currentVersion);
        if($clone === false) {
            throw new LocalizedException(__('Failed to clone active version.'));
        }

        // Load image optimization related snippets and push them
        $snippets = $this->config->getVclSnippets(self::VCL_SNIPPET_PATH);
        foreach($snippets as $key => $value) {
            $snippetData =[
                'name' => Config::FASTLY_MAGENTO_MODULE . '_image_optimization_' . $key,
                'type' => $key,
                'dynamic' => "0",
                'content' => $value,
                'priority' => 10
            ];

            $status = $this->api->uploadSnippet($clone->number, $snippetData);
            if($status == false) {
                throw new LocalizedException(__('Failed to upload the Snippet file.'));
            }
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook(
                '*Image optimization snippet has been pushed in Fastly version '. $clone->number . '*'
            );
        }

        // Validate before sending success
        $validate = $this->api->validateServiceVersion($clone->number);

        if($validate->status == 'error') {
            throw new LocalizedException(__('Failed to validate service version: ' . $validate->msg));
        }

        // Attempt to activate the new Fastly version
        if($activateVcl === 'true') {
            $this->api->activateVersion($clone->number);
        }
    }

    /**
     * Removes image optimiaztion related snippets
     *
     * @param $activateVcl
     * @throws LocalizedException
     */
    protected function removeSnippets($activateVcl)
    {
        // Lets clone it and push the required config
        $clone = $this->api->cloneVersion($this->currentVersion);
        if($clone === false) {
            throw new LocalizedException(__('Failed to clone active version.'));
        }

        // Load image optimization related snippets and push them
        $snippets = $this->config->getVclSnippets(self::VCL_SNIPPET_PATH);
        foreach($snippets as $key => $value) {
            $snippetName = Config::FASTLY_MAGENTO_MODULE . '_image_optimization_' . $key;
            $this->api->removeSnippet($clone->number, $snippetName);
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook(
                '*Image optimization snippet has been removed in Fastly version '. $clone->number . '*'
            );
        }

        // Validate before sending success
        $validate = $this->api->validateServiceVersion($clone->number);

        if($validate->status == 'error') {
            throw new LocalizedException(__('Failed to validate service version: ' . $validate->msg));
        }

        // Attempt to activate the new Fastly version
        if($activateVcl === 'true') {
            $this->api->activateVersion($clone->number);
        }
    }

    /**
     * Validates that current state of service configuration is good
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function validateRequest()
    {
        // Check if service has been initialized
        $service = $this->api->checkServiceDetails();
        if($service === false) {
            throw new LocalizedException(__('Failed to check Service details.'));
        }

        // Get the current version
        $currActiveVersion = $this->vcl->determineVersions($service->versions);
        if($currActiveVersion['active_version'] != $this->currentVersion) {
            throw new LocalizedException(__('Active versions mismatch.'));
        }

        return true;
    }

    /**
     * Adjusts the status of the config
     *
     * @param bool $status
     */
    protected function setStatus($status)
    {
        $this->configWriter->save(Config::XML_FASTLY_IMAGE_OPTIMIZATIONS, (bool)$status);

        /** @var \Magento\Framework\App\Cache\Manager $cacheManager */
        $cacheManager = $this->cacheFactory->create();

        $cacheManager->flush([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
    }
}
