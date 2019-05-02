<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImageOptimization;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Product\Image;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class PushImageSettings
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImageOptimization
 */
class PushImageSettings extends Action
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
    /**
     * @var Image
     */
    private $image;

    /**
     * PushImageSettings constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param Image $image
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        Image $image
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->image = $image;

        parent::__construct($context);
    }

    /**
     * Upload Image Optimization settings
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $imageQualityFlag = $this->getRequest()->getParam('image_quality_flag');
            $imageQuality = $this->image->getQuality();
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $checkIfSettingExists = $this->api->hasSnippet($activeVersion, Config::IMAGE_SETTING_NAME);
            $snippet = $this->config->getVclSnippets(Config::IO_VCL_SNIPPET_PATH, 'recv.vcl');

            if (!$checkIfSettingExists) {
                foreach ($snippet as $key => $value) {
                    $snippetData = [
                        'name' => Config::FASTLY_MAGENTO_MODULE . '_image_optimization_' . $key,
                        'type' => $key,
                        'dynamic' => "0",
                        'content' => $value,
                        'priority' => 10
                    ];

                    $this->api->uploadSnippet($clone->number, $snippetData);

                    $id = $service->id . '-' . $clone->number . '-imageopto';
                    # Make sure we set webp to auto default
                    $imageParams = [
                        'data' => [
                            'id'            => $id,
                            'type'          => 'io_settings',
                            'attributes'    => [
                                'webp'      => true
                            ]
                        ]
                    ];

                    # Set image quality to magento default quality if selected
                    if ($imageQualityFlag === 'true') {
                        $imageParams['data']['attributes']['webp_quality'] = $imageQuality;
                        $imageParams['data']['attributes']['jpeg_quality'] = $imageQuality;
                    }

                    $this->api->configureImageOptimizationDefaultConfigOptions(
                        json_encode($imageParams),
                        $clone->number
                    );
                }
            } else {
                // Remove image optimization snippet
                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE . '_image_optimization_' . $key;
                    if ($this->api->hasSnippet($clone->number, $name) == true) {
                        $this->api->removeSnippet($clone->number, $name);
                    }
                }
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $this->sendWebhook($checkIfSettingExists, $clone);

            $comment = ['comment' => 'Magento Module pushed the Image Optimization snippet'];
            if ($checkIfSettingExists) {
                $comment = ['comment' => 'Magento Module removed the Image Optimization snippet'];
            }
            $this->api->addComment($clone->number, $comment);

            return $result->setData([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    private function sendWebhook($checkIfSettingExists, $clone)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            if ($checkIfSettingExists) {
                $this->api->sendWebHook(
                    '*Image optimization snippet has been removed in Fastly version ' . $clone->number . '*'
                );
            } else {
                $this->api->sendWebHook(
                    '*Image optimization snippet has been pushed in Fastly version ' . $clone->number . '*'
                );
            }
        }
    }
}
