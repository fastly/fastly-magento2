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

use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class CreateBackend
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend
 */
class CreateBackend extends Action
{
    use ValidationTrait;

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
            $address = $this->getRequest()->getParam('address');
            $form = $this->getRequest()->getParam('form');
            $this->validateAddress($address);

            if ($form == 'false') {
                return $result->setData(['status' => true]);
            }

            $override = $this->validateOverride($this->getRequest()->getParam('override_host'));

            $name = $this->getRequest()->getParam('name');
            $this->validateName($name);

            $maxTls = $this->processRequest('max_tls_version');
            $minTls = $this->processRequest('min_tls_version');
            $this->validateVersion((float)$maxTls, (float)$minTls);

            $activate_flag = $this->getRequest()->getParam('activate_flag');
            $activeVersion = $this->getRequest()->getParam('active_version');

            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);

            $tlsYesPort = $this->getRequest()->getParam('tls_yes_port');
            $tlsNoPort = $this->getRequest()->getParam('tls_no_port');

            $port = $tlsYesPort;
            $useSsl = $this->getRequest()->getParam('use_ssl');

            $autoLoadBalance = $this->getRequest()->getParam('auto_loadbalance');

            if ($useSsl != '1') {
                $port = $tlsNoPort;
            }

            $sslCertHostname = $this->processRequest('ssl_cert_hostname');
            $sslSniHostname = $this->processRequest('ssl_sni_hostname');
            $sslCaCert = $this->processRequest('ssl_ca_cert');
            $sslVerifyCert = $this->getRequest()->getParam('ssl_check_cert') === '1' ? true : false;

            if ($sslVerifyCert && !$sslCertHostname) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Certified hostname must be entered if certificate verification is chosen'
                ]);
            }

            $conditionName = $this->getRequest()->getParam('condition_name');
            $applyIf = $this->getRequest()->getParam('apply_if');
            $conditionPriority = $this->getRequest()->getParam('condition_priority');
            $selCondition = $this->getRequest()->getParam('request_condition');

            $condition = $this->createCondition($clone, $conditionName, $applyIf, $conditionPriority, $selCondition);

            $params = [
                'address'               => $this->getRequest()->getParam('address'),
                'auto_loadbalance'      => $autoLoadBalance,
                'between_bytes_timeout' => $this->getRequest()->getParam('between_bytes_timeout'),
                'connect_timeout'       => $this->getRequest()->getParam('connect_timeout'),
                'first_byte_timeout'    => $this->getRequest()->getParam('first_byte_timeout'),
                'max_conn'              => $this->getRequest()->getParam('max_conn'),
                'name'                  => $name,
                'port'                  => $port,
                'request_condition'     => $condition,
                'service_id'            => $service->id,
                'shield'                => $this->getRequest()->getParam('shield'),
                'use_ssl'               => $useSsl,
                'version'               => $clone->number,
                'override_host'         => $override
            ];

            if ($useSsl == '1') {
                $params += [
                    'ssl_ca_cert'           => $sslCaCert,
                    'ssl_check_cert'        => $sslVerifyCert,
                    'ssl_cert_hostname'     => $sslCertHostname,
                    'ssl_ciphers'           => $this->processRequest('ssl_ciphers'),
                    'ssl_client_cert'       => $this->processRequest('ssl_client_cert'),
                    'ssl_client_key'        => $this->processRequest('ssl_client_key'),
                    'ssl_sni_hostname'      => $sslSniHostname,
                    'max_tls_version'       => $maxTls,
                    'min_tls_version'       => $minTls,
                ];
            }

            if ($autoLoadBalance == '1') {
                $params += [
                    'weight' => $this->getRequest()->getParam('weight')
                ];
            }

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
                'comment' => 'Magento Module created the "' . $this->getRequest()->getParam('name') . '" Backend '
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
