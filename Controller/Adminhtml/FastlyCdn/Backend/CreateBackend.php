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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config;

/**
 * Class CreateBackend
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend
 */
class CreateBackend extends Action
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
     * ConfigureBackend constructor
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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activate_flag = $this->getRequest()->getParam('activate_flag');
            $activeVersion = $this->getRequest()->getParam('active_version');

            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);

            $params = [
                'address'               => $this->getRequest()->getParam('address'),
//                'auto_loadbalance'      => $this->getRequest()->getParam(''),
                'between_bytes_timeout' => $this->getRequest()->getParam('between_bytes_timeout'),
                'connect_timeout'       => $this->getRequest()->getParam('connect_timeout'),
                'first_byte_timeout'    => $this->getRequest()->getParam('first_byte_timeout'),
//                'healthcheck'           => $this->getRequest()->getParam(''),
//                'hostname'              => $this->getRequest()->getParam('hostname'),
                'max_conn'              => $this->getRequest()->getParam('max_conn'),
                'max_tls_version'       => $this->getRequest()->getParam('max_tls_version'),
                'min_tls_version'       => $this->getRequest()->getParam('min_tls_version'),
                'name'                  => $this->getRequest()->getParam('name'),
                'port'                  => 80, // is this the tls port
                'request_condition'     => $this->getRequest()->getParam('request_condition'),
                'service_id'            => $service->id,
                'shield'                => $this->getRequest()->getParam('shield'),
//                'ssl_ca_cert'           => $this->getRequest()->getParam('ssl_ca_cert'),
//                'ssl_cert_hostname'     => $this->getRequest()->getParam('ssl_cert_hostname'),
//                'ssl_ciphers'           => $this->getRequest()->getParam('ssl_ciphers'),
//                'ssl_client_cert'       => $this->getRequest()->getParam('ssl_client_cert'),
//                'ssl_client_key'        => $this->getRequest()->getParam('ssl_client_key'),
//                'ssl_sni_hostname'      => $this->getRequest()->getParam('ssl_sni_hostname'),
                'use_ssl'               => $this->getRequest()->getParam('use_ssl'),
                'version'               => $clone->number
            ];

            $createBackend = $this->api->createBackend($params, $clone->number);

            if (!$createBackend) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to create Backend.'
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activate_flag === 'true') {
                $this->api->activateVersion($clone->number);
            }
            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Backend '
                    . $this->getRequest()->getParam('name')
                    . ' has been created in Fastly version '
                    . $clone->number
                    . '*'
                );
            }

            $comment = [
                'comment' => 'Magento Module created the "'.$this->getRequest()->getParam('name').'" Backend '
            ];
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
