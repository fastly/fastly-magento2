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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Cache\InvalidateLogger;
use Fastly\Cdn\Helper\Data;
use Fastly\Cdn\Helper\Vcl;
use Psr\Log\LoggerInterface;

class Api
{
    const FASTLY_HEADER_AUTH   = 'Fastly-Key';
    const FASTLY_HEADER_TOKEN  = 'X-Purge-Token';
    const FASTLY_HEADER_SOFT_PURGE = 'Fastly-Soft-Purge';
    const PURGE_TIMEOUT        = 10;
    const PURGE_TOKEN_LIFETIME = 30;
    const FASTLY_MAX_HEADER_KEY_SIZE = 256;

    /**
     * @var Config $config,
     */
    private $config;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    private $curlFactory;

    /**
     * @var InvalidateLogger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var bool Purge all flag
     */
    private $purged = false;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * Api constructor.
     * @param Config $config
     * @param CurlFactory $curlFactory
     * @param InvalidateLogger $logger
     * @param Data $helper
     * @param LoggerInterface $log
     * @param Vcl $vcl
     */
    public function __construct(
        Config $config,
        CurlFactory $curlFactory,
        InvalidateLogger $logger,
        Data $helper,
        LoggerInterface $log,
        Vcl $vcl
    ) {
        $this->config = $config;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->log = $log;
        $this->vcl = $vcl;
    }

    /**
     * Returns the Fastly API service uri
     *
     * @return string
     */
    private function _getApiServiceUri()
    {
        $uri = $this->config->getApiEndpoint()
            . 'service/'
            . $this->config->getServiceId()
            . '/';

        return $uri;
    }

    /**
     * Historical API stats
     *
     * @return string
     */
    private function _getHistoricalEndpoint()
    {
        $uri = $this->config->getApiEndpoint() . 'stats/service/' . $this->config->getServiceId();

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
            $this->sendWebHook('*clean by URL for* ' . $url);
        }

        return $result;
    }

    /**
     * Purge Fastly by a given surrogate key
     *
     * @param $keys
     * @return bool
     */
    public function cleanBySurrogateKey($keys)
    {
        $uri = $this->_getApiServiceUri() . 'purge';
        $num = count($keys);
        $result = false;
        if ($num >= self::FASTLY_MAX_HEADER_KEY_SIZE) {
            $parts = $num / self::FASTLY_MAX_HEADER_KEY_SIZE;
            $additional = ($parts > (int)$parts) ? 1 : 0;
            $parts = (int)$parts + (int)$additional;
            $chunks = ceil($num/$parts);
            $collection = array_chunk($keys, $chunks);
        } else {
            $collection = [$keys];
        }

        foreach ($collection as $keys) {
            $payload = json_encode(['surrogate_keys' => $keys]);
            if ($result = $this->_purge($uri, \Zend_Http_Client::POST, $payload)) {
                foreach ($keys as $key) {
                    $this->logger->execute('surrogate key: ' . $key);
                }
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishKeyUrlChanges()) {
                $status = $result ? '' : 'FAILED ';
                $this->sendWebHook($status . '*clean by key on ' . join(" ", $keys) . '*');
            }
        }

        return $result;
    }

    /**
     * Purge all of Fastly's CDN content. Can be called only once per request
     *
     * @return bool
     */
    public function cleanAll()
    {
        // Check if purge has been requested on this request
        if ($this->purged == true) {
            return true;
        }
        $this->purged = true;

        $uri = $this->_getApiServiceUri() . 'purge_all';
        if ($result = $this->_purge($uri)) {
            $this->logger->execute('clean all items');
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishPurgeAllChanges()) {
            $this->sendWebHook('*initiated clean/purge all*');

            if ($this->config->canPublishDebugBacktrace() == false) {
                return $result;
            }

            $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $trace = [];
            foreach ($stackTrace as $row => $data) {
                $trace[] = "#{$row} {$data['file']}:{$data['line']} -> {$data['function']}()";
            }

            $this->sendWebHook('*Purge all backtrace:*```' .  implode("\n", $trace) . '```');
        }

        return $result;
    }

    /**
     * Send purge request via Fastly API
     *
     * @param $uri
     * @param string $method
     * @param null $payload
     * @return bool
     */
    private function _purge($uri, $method = \Zend_Http_Client::POST, $payload = null)
    {

        if ($method == 'PURGE') {
            // create purge token
            $expiration   = time() + self::PURGE_TOKEN_LIFETIME;

            $zendUri = \Zend_Uri::factory($uri);
            $path = $zendUri->getPath();
            $stringToSign = $path . $expiration;
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
            array_push(
                $headers,
                self::FASTLY_HEADER_SOFT_PURGE . ': 1'
            );
        }

        try {
            $client = $this->curlFactory->create();
            $client->setConfig(['timeout' => self::PURGE_TIMEOUT]);
            if ($method == 'PURGE') {
                $client->addOption(CURLOPT_CUSTOMREQUEST, 'PURGE');
            }
            $client->write($method, $uri, '1.1', $headers, $payload);
            $responseBody = $client->read();
            $responseCode = \Zend_Http_Response::extractCode($responseBody);
            $client->close();

            // check response
            if ($responseCode != '200') {
                throw new LocalizedException(__('Return status ' . $responseCode));
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
     * @param bool $test
     * @param null $serviceId
     * @param null $apiKey
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function checkServiceDetails($test = false, $serviceId = null, $apiKey = null)
    {
        if (!$test) {
            $uri = rtrim($this->_getApiServiceUri(), '/');
            $result = $this->_fetch($uri);
        } else {
            $uri = $this->config->getApiEndpoint() . 'service/' . $serviceId;
            $result = $this->_fetch($uri, \Zend_Http_Client::GET, null, true, $apiKey);
        }

        if (!$result) {
            throw new LocalizedException(__('Failed to check Service details.'));
        }

        return $result;
    }

    /**
     * Clone the current configuration into a new version.
     * @param $curVersion
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function cloneVersion($curVersion)
    {
        $url = $this->_getApiServiceUri() . 'version/'.$curVersion.'/clone';
        $result = $this->_fetch($url, \Zend_Http_Client::PUT);

        if (!$result) {
            throw new LocalizedException(__('Failed to clone active version.'));
        }

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
     * @param $version
     * @throws LocalizedException
     */
    public function validateServiceVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' .$version. '/validate';
        $result = $this->_fetch($url, 'GET');

        if ($result->status == 'error') {
            throw new LocalizedException(__('Failed to validate service version: ' . $result->msg));
        }
    }

    /**
     * @param $version
     * @return bool|mixed
     */
    public function containerValidateServiceVersion($version)
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
     * @param $version
     * @param array $snippet
     * @throws LocalizedException
     */
    public function uploadSnippet($version, array $snippet)
    {
        // Perform replacements vcl template replacements
        if (isset($snippet['content'])) {
            $adminUrl = $this->vcl->getAdminFrontName();
            $adminPathTimeout = $this->config->getAdminPathTimeout();
            $ignoredUrlParameters = $this->config->getIgnoredUrlParameters();

            $ignoredUrlParameterPieces = explode(",", $ignoredUrlParameters);
            $filterIgnoredUrlParameterPieces = array_filter(array_map('trim', $ignoredUrlParameterPieces));
            $queryParameters = implode('|', $filterIgnoredUrlParameterPieces);

            $snippet['content'] = str_replace('####ADMIN_PATH####', $adminUrl, $snippet['content']);
            $snippet['content'] = str_replace('####ADMIN_PATH_TIMEOUT####', $adminPathTimeout, $snippet['content']);
            $snippet['content'] = str_replace('####QUERY_PARAMETERS####', $queryParameters, $snippet['content']);
        }

        $checkIfExists = $this->hasSnippet($version, $snippet['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/snippet';
        if (!$checkIfExists) {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$snippet['name'];
            unset($snippet['name'], $snippet['type'], $snippet['dynamic'], $snippet['priority']);
        }

        $result = $this->_fetch($url, $verb, $snippet);

        if (!$result) {
            throw new LocalizedException(__('Failed to upload the Snippet file.'));
        }
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
     * Update a dynamic snippet
     *
     * @param array $snippet
     * @return bool|mixed
     */
    public function updateSnippet(array $snippet)
    {
        $url = $this->_getApiServiceUri(). 'snippet' . '/'.$snippet['name'];
        $result = $this->_fetch($url, \Zend_Http_Client::PUT, $snippet);

        return $result;
    }

    /**
     * Performs a lookup to determine if VCL snippet exists
     *
     * @param string    $version    Fastly version
     * @param string    $name   VCL snippet name
     *
     * @return bool
     */
    public function hasSnippet($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . $version . '/snippet/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::GET, '', false, null, false);

        if ($result == false) {
            return false;
        }

        return true;
    }

    /**
     * Deleting an individual regular VCL Snippet
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function removeSnippet($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/snippet/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        return $result;
    }

    /**
     * Creates a new condition
     * @param $version
     * @param array $condition
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createCondition($version, array $condition)
    {
        $checkIfExists = $this->getCondition($version, $condition['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/condition';
        if (!$checkIfExists) {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$condition['name'];
        }

        $result = $this->_fetch($url, $verb, $condition);

        if (!$result) {
            throw new LocalizedException(__('Failed to create a REQUEST condition.'));
        }

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
     * Creates a new header
     *
     * @param $version
     * @param $condition
     * @return bool|mixed
     */
    public function createHeader($version, array $condition)
    {
        $checkIfExists = $this->getHeader($version, $condition['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/header';

        if ($checkIfExists === false) {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$condition['name'];
        }

        $result = $this->_fetch($url, $verb, $condition);

        return $result;
    }

    /**
     * Gets the specified header.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function getHeader($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/header/' . $name;
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
        if (!$checkIfExists) {
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
     * @param $version
     * @param $request
     * @throws LocalizedException
     */
    public function createRequest($version, $request)
    {
        $checkIfExists = $this->getRequest($version, $request['name']);
        $url = $this->_getApiServiceUri(). 'version/' .$version. '/request_settings';
        if (!$checkIfExists) {
            $verb = \Zend_Http_Client::POST;
        } else {
            $verb = \Zend_Http_Client::PUT;
            $url .= '/'.$request['name'];
        }

        $result = $this->_fetch($url, $verb, $request);

        if (!$result) {
            throw new LocalizedException(__('Failed to create the REQUEST object.'));
        }
    }

    /**
     * Retrieves a specific Request settings object.
     *
     * @param string    $version    Fastly version
     * @param string    $name   Request name
     *
     * @return bool
     */
    public function getRequest($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/request_settings/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::GET, '', false, null, false);

        return $result;
    }

    /**
     * Removes the specified Request Settings object.
     * @param $version
     * @param $name
     * @throws LocalizedException
     */
    public function deleteRequest($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version. '/request_settings/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        if (!$result) {
            throw new LocalizedException(__('Failed to delete the REQUEST object.'));
        }
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

    /**
     * Configure Backend settings
     *
     * @param $params
     * @param $version
     * @param $old_name
     * @return bool|mixed
     */
    public function configureBackend($params, $version, $old_name)
    {
        $url = $this->_getApiServiceUri()
            . 'version/'
            . $version
            . '/backend/'
            . str_replace(' ', '%20', $old_name);

        $result = $this->_fetch(
            $url,
            \Zend_Http_Client::PUT,
            $params
        );

        return $result;
    }

    /**
     * Send message to Slack channel
     *
     * @param $message
     */
    public function sendWebHook($message)
    {
        $url = $this->config->getIncomingWebhookURL();
        $messagePrefix = $this->config->getWebhookMessagePrefix();
        $currentUsername = 'System'; // TO DO: Fetch current admin username
        $storeName = $this->helper->getStoreName();
        $storeUrl = $this->helper->getStoreUrl();

        $text =  $messagePrefix.' user='.$currentUsername.' '.$message.' on <'.$storeUrl.'|Store> | '.$storeName;

        $headers = [
            'Content-type: application/json'
        ];

        $body = json_encode([
            "text"  =>  $text,
            "username" => "fastly-magento-bot",
            "icon_emoji"=> ":airplane:"
        ]);

        $client = $this->curlFactory->create();
        $client->addOption(CURLOPT_CONNECTTIMEOUT, 2);
        $client->addOption(CURLOPT_TIMEOUT, 3);
        $client->write(\Zend_Http_Client::POST, $url, '1.1', $headers, $body);
        $response = $client->read();
        $responseCode = \Zend_Http_Response::extractCode($response);

        if ($responseCode != 200) {
            $this->log->log(100, 'Failed to send message to the following Webhook: '.$url);
        }

        $client->close();
    }

    /**
     * Create named dictionary for a particular service and version.
     * @param $version
     * @param $params
     * @throws LocalizedException
     */
    public function createDictionary($version, $params)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/dictionary';
        $result = $this->_fetch($url, \Zend_Http_Client::POST, $params);

        if (!$result) {
            throw new LocalizedException(__('Failed to create Dictionary container.'));
        }
    }

    /**
     * Delete named dictionary for a particular service and version.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function deleteDictionary($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/dictionary/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        return $result;
    }

    /**
     * Get dictionary item list
     * @param $dictionaryId
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function dictionaryItemsList($dictionaryId)
    {
        $url = $this->_getApiServiceUri(). 'dictionary/'.$dictionaryId.'/items';
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Fetches dictionary by name
     * @param $version
     * @param $dictionaryName
     * @return bool|mixed
     */
    public function getSingleDictionary($version, $dictionaryName)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/dictionary/' . $dictionaryName;
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Get auth dictionary
     * @param $version
     * @return bool|mixed
     */
    public function getAuthDictionary($version)
    {
        $name = \Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl\CheckAuthUsersAvailable::AUTH_DICTIONARY_NAME;
        $dictionary = $this->getSingleDictionary($version, $name);

        return $dictionary;
    }

    /**
     * Check if authentication dictionary is populated
     * @param $version
     * @throws LocalizedException
     */
    public function checkAuthDictionaryPopulation($version)
    {
        $dictionary = $this->getAuthDictionary($version);

        if ((is_array($dictionary) && empty($dictionary)) || !isset($dictionary->id)) {
            throw new LocalizedException(__('You must add users in order to enable Basic Authentication.'));
        }

        $authItems = $this->dictionaryItemsList($dictionary->id);

        if (is_array($authItems) && empty($authItems)) {
            throw new LocalizedException(__('You must add users in order to enable Basic Authentication.'));
        }
    }

    public function createDictionaryItems($dictionaryId, $params)
    {
        $url = $this->_getApiServiceUri().'dictionary/'.$dictionaryId.'/items';
        $result = $this->_fetch($url, \Zend_Http_Client::PATCH, $params);

        return $result;
    }

    /**
     * List all dictionaries for the version of the service.
     *
     * @param $version
     * @return bool|mixed
     */
    public function getDictionaries($version)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/dictionary';
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Delete single Dictionary item
     *
     * @param $dictionaryId
     * @param $itemKey
     * @return bool|mixed
     */
    public function deleteDictionaryItem($dictionaryId, $itemKey)
    {
        $url = $this->_getApiServiceUri(). 'dictionary/'. $dictionaryId . '/item/' . urlencode($itemKey);
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        return $result;
    }

    /**
     * Upsert single Dictionary item
     * @param $dictionaryId
     * @param $itemKey
     * @param $itemValue
     * @throws LocalizedException
     */
    public function upsertDictionaryItem($dictionaryId, $itemKey, $itemValue)
    {
        $body = ['item_value' => $itemValue];
        $url = $this->_getApiServiceUri(). 'dictionary/'. $dictionaryId . '/item/' . urlencode($itemKey);
        $result = $this->_fetch($url, \Zend_Http_Client::PUT, $body);

        if (!$result) {
            throw new LocalizedException(__('Failed to create Dictionary item.'));
        }
    }

    /**
     * Create named ACL for a particular service and version.
     *
     * @param $version
     * @param $params
     * @return bool|mixed
     */
    public function createAcl($version, $params)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/acl';
        $result = $this->_fetch($url, \Zend_Http_Client::POST, $params);

        return $result;
    }

    /**
     * Fetch ACL list for particular service and version
     *
     * @param $version
     * @return bool|mixed
     */
    public function getAcls($version)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/acl';
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Delete named acl for a particular service and version.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     */
    public function deleteAcl($version, $name)
    {
        $url = $this->_getApiServiceUri(). 'version/'. $version . '/acl/' . $name;
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        return $result;
    }

    /**
     * Fetch ACL entry list for particular ACL
     *
     * @param $aclId
     * @return bool|mixed
     */
    public function aclItemsList($aclId)
    {
        $url = $this->_getApiServiceUri() . 'acl/'. $aclId . '/entries';
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Upsert single ACL entry
     *
     * @param $aclId
     * @param $itemValue
     * @param $negated
     * @return bool|mixed
     */
    public function upsertAclItem($aclId, $itemValue, $negated, $subnet = false)
    {
        $body = [
            'ip' => $itemValue,
            'negated' => $negated,
            'comment' => 'Added by Magento Module'
        ];

        if ($subnet) {
            $body['subnet'] = $subnet;
        }

        $url = $this->_getApiServiceUri(). 'acl/'. $aclId . '/entry';
        $result = $this->_fetch($url, \Zend_Http_Client::POST, $body);

        return $result;
    }

    /**
     * Delete single ACL entry from specific ACL
     *
     * @param $aclId
     * @param $aclItemId
     * @return bool|mixed
     */
    public function deleteAclItem($aclId, $aclItemId)
    {
        $url = $this->_getApiServiceUri(). 'acl/'. $aclId . '/entry/' . $aclItemId;
        $result = $this->_fetch($url, \Zend_Http_Client::DELETE);

        return $result;
    }

    /**
     * @param array $parameters
     * @return bool|mixed
     */
    public function queryHistoricStats(array $parameters)
    {
        $uri = $this->_getHistoricalEndpoint()
            . '?region='.$parameters['region']
            . '&from='.$parameters['from']
            . '&to='.$parameters['to']
            . '&by='
            . $parameters['sample_rate'];

        $result = $this->_fetch($uri);

        return $result;
    }

    /**
     * Check if image optimization is enabled for the Fastly service
     *
     * @return bool|mixed
     */
    public function checkImageOptimizationStatus()
    {
        $url = $this->_getApiServiceUri(). 'dynamic_io_settings';
        $result = $this->_fetch($url, \Zend_Http_Client::GET);

        return $result;
    }

    /**
     * Wrapper for API calls towards Fastly service
     *
     * @param string    $uri    API Endpoint
     * @param string    $method HTTP Method for request
     * @param mixed[]|string    $body   Content
     * @param bool  $test   Use $testApiKey for request
     * @param string    $testApiKey API key to be tested
     * @param bool  $logError   When set to false, prevents writing failed requests to log
     *
     * @return bool|mixed   Returns false on failiure
     */
    private function _fetch(
        $uri,
        $method = \Zend_Http_Client::GET,
        $body = '',
        $test = false,
        $testApiKey = null,
        $logError = true
    ) {
        $apiKey = ($test == true) ? $testApiKey : $this->config->getApiKey();

        // Corectly format $body string
        if (is_array($body) == true) {
            $body = http_build_query($body);
        }

        // Client headers
        $headers = [
            self::FASTLY_HEADER_AUTH  . ': ' . $apiKey,
            'Accept: application/json'
        ];

        // Client options
        $options = [];

        // Request method specific header & option changes
        switch ($method) {
            case \Zend_Http_Client::DELETE:
                $options[CURLOPT_CUSTOMREQUEST] = \Zend_Http_Client::DELETE;
                break;
            case \Zend_Http_Client::PUT:
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $options[CURLOPT_CUSTOMREQUEST] = \Zend_Http_Client::PUT;

                if ($body != '') {
                    $options[CURLOPT_POSTFIELDS] = $body;
                }

                break;
            case \Zend_Http_Client::PATCH:
                $options[CURLOPT_CUSTOMREQUEST] = \Zend_Http_Client::PATCH;
                $headers[] = 'Content-Type: text/json';

                if ($body != '') {
                    $options[CURLOPT_POSTFIELDS] = $body;
                }

                break;
        }

        /** @var \Magento\Framework\HTTP\Adapter\Curl $client */
        $client = $this->curlFactory->create();

        // Execute request
        $client->setOptions($options);
        $client->write($method, $uri, '1.1', $headers, $body);
        $response = $client->read();
        $client->close();

        // Parse response
        $responseBody = \Zend_Http_Response::extractBody($response);
        $responseCode = \Zend_Http_Response::extractCode($response);

        // Return error based on response code
        if ($responseCode != '200') {
            if ($logError == true) {
                $this->logger->critical('Return status ' . $responseCode, $uri);
            }

            return false;
        }

        return json_decode($responseBody);
    }
}
