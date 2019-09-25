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
 * Class ConfigureBackend
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend
 */
class ConfigureBackend extends Action
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
     * Upload VCL snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $oldName = $this->getRequest()->getParam('old_name');
            $address = $this->getRequest()->getParam('backend_address');
            $this->validateAddress($address);

            $override = $this->validateOverride($this->getRequest()->getParam('override_host'));

            $name = $this->getRequest()->getParam('name');
            $this->validateName($name);

            $maxTls = $this->processRequest('max_tls_version');
            $minTls = $this->processRequest('min_tls_version');
            $this->validateVersion((float)$maxTls, (float)$minTls);

            $activate_flag = $this->getRequest()->getParam('activate_flag') === 'true' ? true : false;
            $activeVersion = $this->getRequest()->getParam('active_version');

            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $tlsYesPort = $this->getRequest()->getParam('tls_yes_port');
            $tlsNoPort = $this->getRequest()->getParam('tls_no_port');

            $port = $tlsYesPort;
            $useSsl = $this->getRequest()->getParam('use_ssl') === '1' ? true : false;

            $autoLoadBalance = $this->getRequest()->getParam('auto_loadbalance') === '1' ? true : false;

            if (!$useSsl) {
                $port = $tlsNoPort;
            }

            $sslVerifyCert = $this->getRequest()->getParam('ssl_check_cert') === '1' ? true : false;
            $sslCertHostname = $this->processRequest('ssl_cert_hostname');

            if ($sslVerifyCert && !$sslCertHostname) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Certified hostname must be entered if certificate verification is chosen'
                ]);
            }

            $sslSniHostname = $this->processRequest('ssl_sni_hostname');
            $sslCaCert = $this->processRequest('ssl_ca_cert');

            $conditionName = $this->getRequest()->getParam('condition_name');
            $applyIf = $this->getRequest()->getParam('apply_if');
            $conditionPriority = $this->getRequest()->getParam('condition_priority');
            $selCondition = $this->getRequest()->getParam('request_condition');

            $condition = $this->createCondition($clone, $conditionName, $applyIf, $conditionPriority, $selCondition);

            $params = [
                'address'               => $address,
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

            if ($useSsl) {
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

            if ($autoLoadBalance !== false) {
                $params += [
                    'weight' => $this->getRequest()->getParam('weight')
                ];
            }

            $configureBackend = $this->api->configureBackend($params, $clone->number, $oldName);

            if (!$configureBackend) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to update Backend.'
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activate_flag !== false) {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Backend '
                    . $this->getRequest()->getParam('name')
                    . ' has been updated at Fastly version '
                    . $clone->number
                    . '*'
                );
            }

            $comment = [
                'comment' => 'Magento Module configured the "' . $this->getRequest()->getParam('name') . '" Backend '
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
