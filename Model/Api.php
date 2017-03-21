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
namespace Fastly\Cdn\Model;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Cache\InvalidateLogger;
use Fastly\Cdn\Helper\Data;
use Psr\Log\LoggerInterface;

class Api
{
    const FASTLY_HEADER_AUTH   = 'Fastly-Key';
    const FASTLY_HEADER_TOKEN  = 'X-Purge-Token';
    const FASTLY_HEADER_SOFT_PURGE = 'Fastly-Soft-Purge';
    const PURGE_TIMEOUT        = 10;
    const PURGE_TOKEN_LIFETIME = 30;

    /**
     * @var Config $config,
     */
    protected $config;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected $curlFactory;

    /**
     * @var InvalidateLogger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Api constructor.
     * @param Config $config
     * @param CurlFactory $curlFactory
     * @param InvalidateLogger $logger
     * @param Data $helper
     * @param LoggerInterface $log
     */
    public function __construct(
        Config $config,
        CurlFactory $curlFactory,
        InvalidateLogger $logger,
        Data $helper,
        LoggerInterface $log
    ) {
        $this->config = $config;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->log = $log;
    }

    /**
     * Returns the Fastly API service uri
     *
     * @return string
     */
    protected function _getApiServiceUri()
    {
        $uri = $this->config->getApiEndpoint()
            . 'service/'
            . $this->config->getServiceId()
            . '/';

        return $uri;
    }

    /**
     * Purge a single URL
     *
     * @param string $url
     * @return bool
     */
    public function cleanUrl($url)
    {
        if ($result = $this->_purge($url, 'PURGE')) {
            $this->logger->execute($url);
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishKeyUrlChanges()) {
            $this->sendWebHook('*clean by URL on*' . $url);
        }

        return $result;
    }

    /**
     * Purge Fastly by a given surrogate key
     *
     * @param string $key
     * @return bool
     */
    public function cleanBySurrogateKey($key)
    {
        $uri = $this->_getApiServiceUri() . 'purge/' . $key;

        if ($result = $this->_purge($uri)) {
            $this->logger->execute('surrogate key: ' . $key);
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishKeyUrlChanges()) {
            $this->sendWebHook('*clean by key on ' . $key . '*');
        }

        return $result;
    }

    /**
     * Purge all of Fastly's CDN content
     *
     * @return bool
     */
    public function cleanAll()
    {
        $uri = $this->_getApiServiceUri() . 'purge_all';
        if ($result = $this->_purge($uri)) {
            $this->logger->execute('clean all items');
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishPurgeAllChanges()) {
            $this->sendWebHook('*initiated clean/purge all action*');
        }

        return $result;
    }

    /**
     * Send purge request via Fastly API
     *
     * @param string $uri
     * @param string $method
     *
     * @return bool
     * @throws \Exception
     */
    protected function _purge($uri, $method = \Zend_Http_Client::POST)
    {

        if($method == 'PURGE') {
            // create purge token
            $expiration   = time() + self::PURGE_TOKEN_LIFETIME;
            $stringToSign = parse_url($uri, PHP_URL_PATH) . $expiration;
            $signature    = hash_hmac('sha1', $stringToSign, $this->config->getServiceId());
            $token        = $expiration . '_' . urlencode($signature);
            $headers = [
                self::FASTLY_HEADER_TOKEN . ': ' . $token
            ];

        } else {

            // set headers
            $headers = [
                self::FASTLY_HEADER_AUTH  . ': ' . $this->config->getApiKey()
            ];

        }

        // soft purge if needed
        if ($this->config->canUseSoftPurge()) {
            array_push( $headers, self::FASTLY_HEADER_SOFT_PURGE . ': 1' );
        }

        try {
            $client = $this->curlFactory->create();
            $client->setConfig(['timeout' => self::PURGE_TIMEOUT]);
            if($method == 'PURGE') {
                $client->addOption(CURLOPT_CUSTOMREQUEST, 'PURGE');
            }
            $client->write($method, $uri, '1.1', $headers);
            $responseBody = $client->read();
            $responseCode = \Zend_Http_Response::extractCode($responseBody);
            $client->close();

            // check response
            if ($responseCode != '200') {
                throw new \Exception('Return status ' . $responseCode);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $uri);
            return false;
        }

        return true;
    }


    /**
     * Get the logged in customer details
     *
     * @return bool|mixed
     */
    public function getCustomerInfo()
    {
        $uri = $this->config->getApiEndpoint() . 'current_customer';
        $result = $this->_fetch($uri);

        return $result;
    }

    /**
     * List detailed information on a specified service
     *
     * @param bool $test
     * @param $serviceId
     * @param $apiKey
     * @return bool|mixed
     */
    public function checkServiceDetails($test = false, $serviceId = null, $apiKey = null)
    {
        if(!$test) {
            $uri = rtrim($this->_getApiServiceUri(), '/');
            $result = $this->_fetch($uri);
        } else {
            $uri = $this->config->getApiEndpoint() . 'service/' . $serviceId;
            $result = $this->_fetch($uri, \Zend_Http_Client::GET, null, true, $apiKey);
        }

        return $result;
    }

    /**
     * Clone the current configuration into a new version.
     *
     * @param $curVersion
     * @return bool|mixed
     */
    public function cloneVersion($curVersion)
    {
        $url = $this->_getApiServiceUri() . 'version/'.$curVersion.'/clone';
        $result = $this->_fetch($url, \Zend_Http_Client::PUT);

        return $result;
    }

    /**
     * Upload a VCL for a particular service and version
     *
     * @param array $vcl
     * @param $version
     * @return bool|mixed
     */
    public function uploadVcl($version, $vcl)
    {
        $url = $this->_getApiServiceUri() . 'version/' .$version. '/vcl';
        $result = $this->_fetch($url, 'POST', $vcl);

        return $result;
    }

    /**
     * Set the specified VCL as the main
     *
     * @param $version
     * @param string $name
     * @return bool|mixed
     */
    public function setVclAsMain($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' .$version. '/vcl/' .$name. '/main';
        $result = $this->_fetch($url, 'PUT');

        return $result;
    }

    /**
     * Validate the version for a particular service and version.
     *
     * @param $version
     * @return bool|mixed
     */
    public function validateServiceVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' .$version. '/validate';
        $result = $this->_fetch($url, 'GET');

        return $result;
    }

    /**
     * Activate the current version.
     *
     * @param $version
     * @return bool|mixed
     */
    public function activateVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' .$version. '/activate';
        $result = $this->_fetch($url, 'PUT');

        return $result;
    }

    /**
     * Creating and updating a regular VCL Snippet
     *
     * @param $version
     * @param array $snippet
     * @return bool|mixed*
     */
    public function uploadSnippet($version, array $snippet)
    {
        $checkIfExists = $this->getSnippet($version, $snippet['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/snippet';
        if(!$checkIfExists)
        {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$snippet['name'];
            unset($snippet['name'], $snippet['type'], $snippet['dynamic'], $snippet['priority']);
        }

        $result = $this->_fetch($url, $verb, $snippet);

        return $result;
    }

    /**
     * Fetching an individual regular VCL Snippet
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function getSnippet($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/snippet/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Creates a new condition
     *
     * @param $version
     * @param $condition
     * @return bool|mixed
     */
    public function createCondition($version, array $condition)
    {
        $checkIfExists = $this->getCondition($version, $condition['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/condition';
        if(!$checkIfExists)
        {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$condition['name'];
        }

        $result = $this->_fetch($url, $verb, $condition);

        return $result;
    }

    /**
     * Gets the specified condition.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function getCondition($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/condition/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Creates a new Response Object.
     *
     * @param $version
     * @param array $response
     * @return bool $result
     */
    public function createResponse($version, array $response)
    {
        $checkIfExists = $this->getResponse($version, $response['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/response_object';
        if(!$checkIfExists)
        {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$response['name'];
        }

        $result = $this->_fetch($url, $verb, $response);

        return $result;
    }

    /**
     * Gets the specified Response Object.
     *
     * @param string $version
     * @param string $name
     * @return bool|mixed $result
     */
    public function getResponse($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/response_object/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Creates a new Request Settings object.
     *
     * @param $version
     * @param $request
     * @return bool|mixed
     */
    public function createRequest($version, $request)
    {
        $checkIfExists = $this->getRequest($version, $request['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/request_settings';
        if(!$checkIfExists)
        {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$request['name'];
        }

        $result = $this->_fetch($url, $verb, $request);

        return $result;
    }

    /**
     * Gets the specified Request Settings object.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function getRequest($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/request_settings/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Removes the specified Request Settings object.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function deleteRequest($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/request_settings/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        return $result;
    }

    /**
     * List all backends for a particular service and version.
     *
     * @param $version
     * @return bool|mixed
     */
    public function getBackends($version)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/backend';
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    public function configureBackend($params, $version, $old_name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/backend/' . str_replace ( ' ', '%20', $old_name);
        $result = $this->_fetch($url, \Zend_Http_Client::PUT, $params);

        return $result;
    }

    public function sendWebHook($message)
    {
        $url = $this->config->getIncomingWebhookURL();
        $text = str_replace("###MESSAGE###", $message, $this->config->getWebhookMessageFormat());
        $text .= ' on <'.$this->helper->getStoreUrl().'|Store URL> | '.$this->helper->getStoreName();

        $headers = [
            'Content-type: application/json'
        ];

        $body = json_encode(array(
            "text"  =>  $text,
            "username" => "fastly-magento-bot",
            "icon_emoji"=> ":airplane:"
        ));

        $client = $this->curlFactory->create();
        $client->addOption(CURLOPT_CONNECTTIMEOUT, 2);
        $client->addOption(CURLOPT_TIMEOUT, 3);
        $client->write(\Zend_Http_Client::POST, $url, '1.1', $headers, $body);
        $response = $client->read();
        $responseCode = \Zend_Http_Response::extractCode($response);

        if ($responseCode == 200) {
            $this->log->log(100, 'Failed to send message to the following Webhook: '.$url);
        }

        $client->close();
    }

    /**
     * @param $uri
     * @param string $method
     * @param string $body
     * @param bool $test
     * @param $testApiKey
     * @return bool|mixed
     */
    protected function _fetch($uri, $method = \Zend_Http_Client::GET, $body = '', $test = false, $testApiKey = null)
    {

        if($test) {
            $apiKey = $testApiKey;
        } else {
            $apiKey = $this->config->getApiKey();
        }

        // set headers
        $headers = [
            self::FASTLY_HEADER_AUTH  . ': ' . $apiKey,
            'Accept: application/json'
        ];

        if($method == \Zend_Http_Client::PUT) {
            array_push($headers, 'Content-Type: application/x-www-form-urlencoded');
        }

        try {
            $client = $this->curlFactory->create();
            if($method == \Zend_Http_Client::PUT) {
                $client->addOption(CURLOPT_CUSTOMREQUEST, 'PUT');
                if($body != '')
                {
                    $client->addOption(CURLOPT_POSTFIELDS, http_build_query($body));
                }
            } elseif($method == \Zend_Http_Client::DELETE) {
                $client->addOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
            }
            $client->write($method, $uri, '1.1', $headers, $body);
            $response = $client->read();
            $responseBody = \Zend_Http_Response::extractBody($response);
            $responseCode = \Zend_Http_Response::extractCode($response);
            $client->close();

            // check response
            if ($responseCode != '200') {
                throw new \Exception('Return status ' . $responseCode);
            }

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $uri);
            return false;
        }

        return json_decode($responseBody);
    }
}
