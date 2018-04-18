<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config;

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
     * @var \Fastly\Cdn\Model\Api
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
        try {
            $result = $this->resultJson->create();
            $activate_flag = $this->getRequest()->getParam('activate_flag');
            $activeVersion = $this->getRequest()->getParam('active_version');
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
