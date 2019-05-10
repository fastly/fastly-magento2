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
use Magento\Framework\Exception\LocalizedException;

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
            $address = $this->getRequest()->getParam('address');
            $form = $this->getRequest()->getParam('form');
            $this->validateAddress($address);

            if ($form == 'false') {
                return $result->setData(['status' => true]);
            }

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

            //$this->verifySslCert($sslCertHostname, $sslSniHostname, $port, $sslCaCert);

            $params = [
                'address'               => $this->getRequest()->getParam('address'),
                'auto_loadbalance'      => $autoLoadBalance,
                'between_bytes_timeout' => $this->getRequest()->getParam('between_bytes_timeout'),
                'connect_timeout'       => $this->getRequest()->getParam('connect_timeout'),
                'first_byte_timeout'    => $this->getRequest()->getParam('first_byte_timeout'),
                'max_conn'              => $this->getRequest()->getParam('max_conn'),
                'name'                  => $name,
                'port'                  => $port,
                'request_condition'     => $this->getRequest()->getParam('request_condition'),
                'service_id'            => $service->id,
                'shield'                => $this->getRequest()->getParam('shield'),
                'use_ssl'               => $useSsl,
                'version'               => $clone->number
            ];

            if ($useSsl == '1') {
                $params += [
                    'ssl_ca_cert'           => $sslCaCert,
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

    private function verifySslCert($sslCertHostname, $sslSniHostname, $port, $sslCaCert)
    {
        if (filter_var($sslCertHostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $user['ip'] = "[" . $sslCertHostname . "]";
        } elseif (filter_var($sslCertHostname, FILTER_VALIDATE_IP)) {
            $user['ip'] = $sslCertHostname;
        } else {
            $user['ip'] = gethostbyname($sslCertHostname);
            # If we get the same thing that we started with name is not resolvable
            if ($user['ip'] == $sslCertHostname) {
                die("Address is not an IP and I can't resolve it. Doing nothing");
            } else {
                $user['ip'] = trim($sslCertHostname);
            }
        }

        if (isset($sslSniHostname) && preg_match(
            "/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i",
            $sslSniHostname
        )) {
            $sni_name = $sslSniHostname;
        } else {
            $sni_name = "";
        }

        if (!isset($port)) {
            $port = 443;
        } else {
            $port = is_numeric($port) && $port > 1 && $port < 65536 ? $port : 443;
        }

        if ($sni_name) {
            $sslOptions["SNI_enabled"] = true;
            # Need to figure out why this doesn't work
            $sslOptions["verify_peer"] = false;
            $sslOptions["SNI_server_name"] = $sni_name;
        } else {
            $sslOptions["SNI_enabled"] = false;
        }

        if ($sni_name) {
            $results = $this->checkCertificateChain($user['ip'], $port, $sni_name, $sslCaCert);
        } else {
            $results = $this->checkCertificateChain($user['ip'], $port, "", $sslCaCert);
        }

        if ($results["success"]) {
            print "<div id=\"ssl_cert_results\">";
        } else {
            print "<div id=\"ssl_cert_results\" class=\"ssl-cert-invalid\">";
            print "<h2><font color=red>This certificate is invalid</font></h2>";
            print "Possible reasons (not exhaustive):<br>";
            print htmlentities($results["message"]);
        }
    }

    private function checkCertificateChain($hostname, $port, $sni_hostname, $ca_certs, $debug = 0)
    {
        $sslOptions = [
            "capture_peer_cert_chain"   => false,
            "allow_self_signed"         => false,
            "verify_peer_name"          => true,
            "verify_peer"               => true
        ];

        $ctx = stream_context_create(["ssl" => $sslOptions]);

        $fp = stream_socket_client(
            "ssl://$hostname:$port",
            $errno,
            $errstr,
            4,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (!$fp) {
            $success = 0;
        } else {
            fclose($fp);
            $success = 1;
        }

        $sslOptions = [
            "capture_peer_cert_chain"   => true,
            "allow_self_signed"         => true,
            "verify_peer_name"          => false,
            "verify_peer"               => false
        ];

        if ($sni_hostname != "") {
            $sslOptions["SNI_enabled"] = true;
            # Need to figure out why this doesn't work
            $sslOptions["SNI_server_name"] = $sni_hostname;
        } else {
            $sslOptions["SNI_enabled"] = false;
        }

        $ctx = stream_context_create(["ssl" => $sslOptions]);

        $fp = stream_socket_client(
            "ssl://${hostname}:${port}",
            $errno,
            $errstr,
            4,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        $captured_certs = [];

        $cont = stream_context_get_params($fp);
        if (!$fp) {
            echo "$errstr ($errno)<br />\n";
        } else {
            # Let's go through captured certificates
            foreach ($cont["options"]["ssl"]["peer_certificate_chain"] as $cert) {
                $parsed_cert = openssl_x509_parse($cert);
                $host_cert = isset($parsed_cert["extensions"]["basicConstraints"]) &&
                $parsed_cert["extensions"]["basicConstraints"] == "CA:FALSE" ? 1 : 0;
                if ($host_cert) {
                    $issuer_cn = $parsed_cert["issuer"]["CN"];
                }
                # Let's derive full ISSUER name
                $issuer_name = "";
                if (isset($parsed_cert["issuer"])) {
                    foreach ($parsed_cert["issuer"] as $key => $value) {
                        $issuer_name .= "/" . $key . "=" . $value;
                    }
                }

                $parsed_cert["ISSUER_NAME"] = $issuer_name;

                ksort($parsed_cert);
                $captured_certs[] = $parsed_cert;

                $subject_cn = $parsed_cert["subject"]["CN"];
                $certificates[$subject_cn] = [
                    $parsed_cert["subject"]["CN"],
                    "issuer_cn" => $parsed_cert["issuer"]["CN"],
                    "host_cert" => $host_cert
                ];
            }
        }

        $end = 1;

        # Keep how many times we have gone through the chain to avoid an infinite loop
        # in case of an unforeseen issue
        $count = 0;
    }

    /**
     * @param $address
     * @throws LocalizedException
     */
    private function validateAddress($address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP) &&
            !filter_var($address, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new LocalizedException(__('Address '.$address.' is not a valid IPv4, IPv6 or hostname'));
        }
    }

    /**
     * @param $name
     * @throws LocalizedException
     */
    private function validateName($name)
    {
        if (trim($name) == "") {
            throw new LocalizedException(__("Name can't be blank"));
        }
    }

    /**
     * @param $maxTls
     * @param $minTls
     * @throws LocalizedException
     */
    private function validateVersion($maxTls, $minTls)
    {
        if ($maxTls == 0) {
            return;
        } elseif ($maxTls < $minTls) {
            throw new LocalizedException(__("Maximum TLS version must be higher than the minimum TLS version."));
        }
    }

    /**
     * @param $param
     * @return mixed|null
     */
    private function processRequest($param)
    {
        $request = $this->getRequest()->getParam($param);
        if ($request == '') {
            return null;
        }
        return $request;
    }
}
