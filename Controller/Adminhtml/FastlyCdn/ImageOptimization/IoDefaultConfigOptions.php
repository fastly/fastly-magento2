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
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config;

/**
 * Class IoDefaultConfigOptions
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImageOptimization
 */
class IoDefaultConfigOptions extends Action
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
     * @var Api
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
     * IoDefaultConfigOptions constructor.
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
     * Upload snippet with updated IO default config options
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activate_flag = $this->getRequest()->getParam('activate_flag');
            $activeVersion = $this->getRequest()->getParam('active_version');
            $formData = $this->getRequest()->getParams();
            if (in_array("", $formData)) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Please fill in the required fields.'
                ]);
            }
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $id = $service->id . '-' . $clone->number . '-imageopto';

            $params = json_encode([
                'data' => [
                    'id' => $id,
                    'type' => 'io_settings',
                    'attributes' => [
                        'webp'          => $this->getRequest()->getParam('webp'),
                        'webp_quality'  => $this->getRequest()->getParam('webp_quality'),
                        'jpeg_type'     => $this->getRequest()->getParam('jpeg_type'),
                        'jpeg_quality'  => $this->getRequest()->getParam('jpeg_quality'),
                        'upscale'       => $this->getRequest()->getParam('upscale'),
                        'resize_filter' => $this->getRequest()->getParam('resize_filter')
                    ]
                ]
            ]);

            $configureIo = $this->api->configureImageOptimizationDefaultConfigOptions($params, $clone->number);

            if (!$configureIo) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to update image optimization default config options.'
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activate_flag === 'true') {
                $this->api->activateVersion($clone->number);
            }
            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook('*Image optimization default config options have been updated*');
            }

            $comment = ['comment' => 'Magento Module updated the Image Optimization Default Configuration'];
            $this->api->addComment($clone->number, $comment);

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
