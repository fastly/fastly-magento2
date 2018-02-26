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
    const CONDITION_NAME = 'fastly-image-optimizer-condition';
    const HEADER_NAME = 'fastly-image-optimizer-header';
    const VCL_SNIPPET_PATH = '/vcl_snippets_image_optimizations';

    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ManagerFactory
     */
    private $cacheFactory;
    /**
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * @var int Current Fastly version
     */
    private $currentVersion;

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
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->cacheFactory = $cacheManagerFactory;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $checkOnly = $this->getRequest()->getParam('check_only');
            $status = $this->config->isImageOptimizationEnabled();

            if ($checkOnly == true) {
                $result->setData([
                    'status' => true,
                    'setting_value' => $status
                ]);

                return $result;
            }

            if ($status == true) {
                $this->removeSnippets($currActiveVersion, $result, $status);
                $status = false;
            } else {
                $this->pushSnippets($currActiveVersion, $result, $status);
                $status = true;
            }

            $this->setStatus($status);
            $result->setData([
                'status' => true,
                'new_state' => $status
            ]);

            return $result;
        } catch (\Exception $e) {
            return $result->setData([
                'status' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    private function pushSnippets($currActiveVersion, $result, $status)
    {
        try {
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $clone = $this->api->cloneVersion($currActiveVersion);
            $snippet = $this->config->getVclSnippets(self::VCL_SNIPPET_PATH, 'recv.vcl');

            foreach ($snippet as $key => $value) {
                $snippetData = [
                    'name' => Config::FASTLY_MAGENTO_MODULE . '_image_optimization_' . $key,
                    'type' => $key,
                    'dynamic' => "0",
                    'content' => $value,
                    'priority' => 10
                ];
                $this->api->uploadSnippet($clone->number, $snippetData);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Image optimization snippet has been pushed in Fastly version ' . $clone->number . '*'
                );
            }

            // Validate before sending success
            $this->api->validateServiceVersion($clone->number);
            $this->setStatus($status);
            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    private function removeSnippets($currActiveVersion, $result, $status)
    {
        try {
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $clone = $this->api->cloneVersion($currActiveVersion);
            $snippet = $this->config->getVclSnippets(self::VCL_SNIPPET_PATH, 'recv.vcl');

            foreach ($snippet as $key => $value) {
                $snippetName = Config::FASTLY_MAGENTO_MODULE . '_image_optimization_' . $key;
                $this->api->removeSnippet($clone->number, $snippetName);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Image optimization snippet has been removed in Fastly version ' . $clone->number . '*'
                );
            }

            $this->setStatus($status);
            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * Adjusts the status of the config
     *
     * @param bool $status
     */
    private function setStatus($status)
    {
        $this->configWriter->save(Config::XML_FASTLY_IMAGE_OPTIMIZATIONS, (bool)$status);

        /** @var \Magento\Framework\App\Cache\Manager $cacheManager */
        $cacheManager = $this->cacheFactory->create();

        $cacheManager->flush([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
    }
}
