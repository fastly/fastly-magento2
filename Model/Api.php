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

use Fastly\Cdn\Helper\Data;
use Fastly\Cdn\Helper\Vcl;
use Laminas\Http\ClientFactory;
use Laminas\Http\HeadersFactory;
use Laminas\Http\Request;
use Laminas\Http\RequestFactory;
use Laminas\Uri\UriFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\State;
use Magento\Framework\Cache\InvalidateLogger;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Class Api for sending request to fastly
 */
class Api
{
    public const FASTLY_HEADER_AUTH   = 'Fastly-Key';
    public const FASTLY_HEADER_TOKEN  = 'X-Purge-Token';
    public const FASTLY_HEADER_SOFT_PURGE = 'Fastly-Soft-Purge';
    public const PURGE_TIMEOUT        = 10;
    public const PURGE_TOKEN_LIFETIME = 30;
    public const FASTLY_MAX_HEADER_KEY_SIZE = 256;
    public const UPSERT_ITEMS_MAX_COUNT = 200;

    /**
     * @var Config
     */
    private $config;
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
     * @var Session
     */
    private $authSession;
    /**
     * @var State
     */
    private $state;
    /**
     * @var
     */
    private $errorMessage;
    /**
     * @var ClientFactory
     */
    private $clientFactory;
    /**
     * @var RequestFactory
     */
    private $requestFactory;
    /**
     * @var HeadersFactory
     */
    private $headersFactory;

    /**
     * @param Config $config
     * @param InvalidateLogger $logger
     * @param Data $helper
     * @param LoggerInterface $log
     * @param Vcl $vcl
     * @param Session $authSession
     * @param State $state
     * @param ClientFactory $clientFactory
     * @param RequestFactory $requestFactory
     * @param HeadersFactory $headersFactory
     */
    public function __construct(
        Config $config,
        InvalidateLogger $logger,
        Data $helper,
        LoggerInterface $log,
        Vcl $vcl,
        Session $authSession,
        State $state,
        ClientFactory        $clientFactory,
        RequestFactory       $requestFactory,
        HeadersFactory $headersFactory
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->log = $log;
        $this->vcl = $vcl;
        $this->authSession = $authSession;
        $this->state = $state;
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->headersFactory = $headersFactory;
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
            . rawurlencode($this->config->getServiceId() ?? '')
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
        $uri = $this->config->getApiEndpoint() . 'stats/service/' .
            rawurlencode($this->config->getServiceId() ?? '');

        return $uri;
    }

    private function _getWafEndpoint()
    {
        $uri = $this->config->getApiEndpoint() . 'waf/firewalls/';

        return $uri;
    }

    /**
     * Purge a single URL
     *
     * @param $url
     * @return array|\Magento\Framework\Controller\Result\Json
     */
    public function cleanUrl($url)
    {
        $result = $this->_purge($url, 'PURGE', 'PURGE');

        if ($result['status']) {
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
     * @return bool|mixed
     */
    public function cleanBySurrogateKey($keys)
    {
        $keys = array_filter($keys);
        if (empty($keys)) {
            return true;
        }

        $type = 'clean by key on ';
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
            $result = $this->_purge($uri, null, Request::METHOD_POST, $payload);
            if ($result['status']) {
                foreach ($keys as $key) {
                    $this->logger->execute('surrogate key: ' . $key);
                }
            }

            $canPublishKeyUrlChanges = $this->config->canPublishKeyUrlChanges();
            $canPublishPurgeChanges = $this->config->canPublishPurgeChanges();

            if ($this->config->areWebHooksEnabled() && ($canPublishKeyUrlChanges || $canPublishPurgeChanges)) {
                $status = $result['status'] ? '' : 'FAILED ';
                $this->sendWebHook($status . '*clean by key on ' . join(" ", $keys) . '*');

                $canPublishPurgeByKeyDebugBacktrace = $this->config->canPublishPurgeByKeyDebugBacktrace();
                $canPublishPurgeDebugBacktrace = $this->config->canPublishPurgeDebugBacktrace();

                if ($canPublishPurgeByKeyDebugBacktrace == false && $canPublishPurgeDebugBacktrace == false) {
                    return $result['status'];
                }

                $this->stackTrace($type . join(" ", $keys));
            }
        }

        return $result['status'];
    }

    /**
     * Purge all of Fastly's CDN content. Can be called only once per request
     *
     * @return bool|mixed
     */
    public function cleanAll()
    {
        // Check if purge has been requested on this request
        if ($this->purged == true) {
            return true;
        }
        $this->purged = true;

        $type = 'clean/purge all';
        $uri = $this->_getApiServiceUri() . 'purge_all';
        $result = $this->_purge($uri, null);
        if ($result['status']) {
            $this->logger->execute('clean all items');
        }

        $canPublishPurgeAllChanges = $this->config->canPublishPurgeAllChanges();
        $canPublishPurgeChanges = $this->config->canPublishPurgeChanges();

        if ($this->config->areWebHooksEnabled() && ($canPublishPurgeAllChanges || $canPublishPurgeChanges)) {
            $this->sendWebHook('*initiated clean/purge all*');

            $canPublishPurgeAllDebugBacktrace = $this->config->canPublishPurgeAllDebugBacktrace();
            $canPublishPurgeDebugBacktrace = $this->config->canPublishPurgeDebugBacktrace();

            if ($canPublishPurgeAllDebugBacktrace == false && $canPublishPurgeDebugBacktrace == false) {
                return $result['status'];
            }

            $this->stackTrace($type);
        }

        return $result['status'];
    }

    /**
     * Send purge request via Fastly API
     *
     * @param $uri
     * @param $type
     * @param $method
     * @param $payload
     * @return array
     */
    private function _purge($uri, $type, $method = Request::METHOD_POST, $payload = null)
    {
        $request = $this->requestFactory->create();
        $headers = $this->headersFactory->create();
        if ($method == 'PURGE') {
            // create purge token
            $expiration   = time() + self::PURGE_TOKEN_LIFETIME;

            $laminasUri = UriFactory::factory($uri);
            $path = $laminasUri->getPath();
            $stringToSign = $path . $expiration;
            $signature    = hash_hmac('sha1', $stringToSign, $this->config->getServiceId());
            $token        = $expiration . '_' . urlencode($signature);
            $headers->addHeaderLine(self::FASTLY_HEADER_TOKEN . ': ' . $token);
        } else {
            // set headers
            $headers->addHeaderLine(self::FASTLY_HEADER_AUTH . ': ' . $this->config->getApiKey());
        }

        // soft purge if needed
        if ($this->config->canUseSoftPurge()) {
            $headers->addHeaderLine(self::FASTLY_HEADER_SOFT_PURGE . ': 1');
        }
        $result['status'] = true;
        try {
            $client = $this->clientFactory->create();

            $client->setOptions([
                'timeout'      => self::PURGE_TIMEOUT,
                'httpversion' => '1.1'
            ]);

            $request->setUri($uri);
            $request->setMethod($method);
            if ($payload) {
                $request->setContent($payload);
            }
            $request->setHeaders($headers);
            $response = $client->send($request);
            $responseCode = $response->getStatusCode();
            $responseMessage = $response->getReasonPhrase();

            // check response
            if ($responseCode == '429') {
                throw new LocalizedException(__($responseMessage));
            } elseif ($responseCode != '200') {
                throw new LocalizedException(__($responseCode . ': ' . $responseMessage));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), $uri);
            $result['status'] = false;
            $result['msg'] = $e->getMessage();
        }

        if (empty($type)) {
            return $result;
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishPurgeChanges()) {
            $this->sendWebHook('*initiated ' . $type . '*');

            if ($this->config->canPublishPurgeDebugBacktrace() == false) {
                return $result;
            }

            $this->stackTrace($type);
        }

        return $result;
    }

    /**
     * Get the logged in customer details
     *
     * @return bool|mixed
     * @throws LocalizedException
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
     * @param null $serviceId
     * @param null $apiKey
     * @param bool $isInitialCheck - flag for testing credentials before Service ID and token are saved to config
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function checkServiceDetails($test = false, $serviceId = null, $apiKey = null, $isInitialCheck = false)
    {
        if (!$isInitialCheck && !$this->config->isServiceConfigured()) {
            throw new LocalizedException(__('Fastly service is not configured.'));
        }

        if (!$test) {
            $uri = rtrim($this->_getApiServiceUri(), '/');
            $result = $this->_fetch($uri);
        } else {
            $uri = $this->config->getApiEndpoint() . 'service/' . rawurlencode($serviceId ?? '');
            $result = $this->_fetch($uri, Request::METHOD_GET, null, true, $apiKey);
        }

        if (!$result) {
            throw new LocalizedException(__('Failed to check Service details.'));
        }

        return $result;
    }

    /**
     * Clone the current configuration into a new version.
     *
     * @param $curVersion
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function cloneVersion($curVersion)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($curVersion ?? '') . '/clone';
        $result = $this->_fetch($url, Request::METHOD_PUT);

        if (!$result) {
            throw new LocalizedException(__('Failed to clone active version.'));
        }

        return $result;
    }

    /**
     * Add comment to the specified version
     *
     * @param $version
     * @param $comment
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function addComment($version, $comment)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '');
        $result = $this->_fetch($url, Request::METHOD_PUT, $comment);

        return $result;
    }

    /**
     * Upload a VCL for a particular service and version
     *
     * @param array $vcl
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function uploadVcl($version, $vcl)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/vcl';
        $result = $this->_fetch($url, 'POST', $vcl);

        return $result;
    }

    /**
     * Set the specified VCL as the main
     *
     * @param $version
     * @param string $name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function setVclAsMain($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/vcl/' . rawurlencode($name ?? '') . '/main';
        $result = $this->_fetch($url, 'PUT');

        return $result;
    }

    /**
     * Validate the version for a particular service and version.
     *
     * @param $version
     * @throws LocalizedException
     */
    public function validateServiceVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/validate';
        $result = $this->_fetch($url, 'GET');

        if ($result->status == 'error') {
            throw new LocalizedException(__('Failed to validate service version: ' . $result->msg));
        }
    }

    /**
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function containerValidateServiceVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/validate';
        $result = $this->_fetch($url, 'GET');

        return $result;
    }

    /**
     * Activate the current version.
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function activateVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/activate';
        $result = $this->_fetch($url, 'PUT');

        return $result;
    }

    /**
     * Creating and updating regular VCL snippets
     *
     * @param $version
     * @param array $snippet
     * @throws LocalizedException
     * @throws \Exception
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function uploadSnippet($version, array $snippet)
    {
        // Perform replacements vcl template replacements
        if (isset($snippet['content'])) {
            $adminUrl = $this->vcl->getAdminFrontName();
            $adminPathTimeout = $this->config->getAdminPathTimeout();
            $ignoredUrlParameters = (string)$this->config->getIgnoredUrlParameters();

            if ($ignoredUrlParameters === "") {
                $queryParameters = '&';
            } else {
                $ignoredUrlParameterPieces = explode(",", $ignoredUrlParameters);
                $filterIgnoredUrlParameterPieces = array_filter(array_map('trim', $ignoredUrlParameterPieces));
                $queryParameters = implode('|', $filterIgnoredUrlParameterPieces);
            }

            $snippet['content'] = str_replace('####ADMIN_PATH####', $adminUrl, $snippet['content']);
            $snippet['content'] = str_replace('####ADMIN_PATH_TIMEOUT####', $adminPathTimeout, $snippet['content']);
            $snippet['content'] = str_replace('####QUERY_PARAMETERS####', $queryParameters, $snippet['content']);
        }

        $snippetName = $snippet['name'];
        $checkIfExists = $this->hasSnippet($version, $snippetName);
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/snippet';

        if (!$checkIfExists) {
            $verb = Request::METHOD_POST;
        } else {
            $verb = Request::METHOD_PUT;
            if (!isset($snippet['dynamic']) || $snippet['dynamic'] != 1) {
                $url .= '/' . rawurlencode($snippetName ?? '');
                unset($snippet['name'], $snippet['type'], $snippet['dynamic']);
            } else {
                $snippet['name'] = $this->getSnippet($version, $snippetName)->id;
                $url = $this->_getApiServiceUri() . 'snippet' . '/' . rawurlencode($snippet['name'] ?? '');
            }
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
     * @throws LocalizedException
     */
    public function getSnippet($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/snippet/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Update a dynamic snippet
     *
     * @param array $snippet
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function updateSnippet(array $snippet)
    {
        $url = $this->_getApiServiceUri() . 'snippet' . '/' . rawurlencode($snippet['name'] ?? '');
        $result = $this->_fetch($url, Request::METHOD_PUT, $snippet);

        if (!$result) {
            throw new LocalizedException(__($this->errorMessage));
        }

        return $result;
    }

    /**
     * Performs a lookup to determine if VCL snippet exists
     *
     * @param string    $version    Fastly version
     * @param string    $name   VCL snippet name
     *
     * @return bool
     * @throws LocalizedException
     */
    public function hasSnippet($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/snippet/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_GET, '', false, null, false);

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
     * @throws LocalizedException
     */
    public function removeSnippet($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/snippet/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

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
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/condition';
        if (!$checkIfExists) {
            $verb = Request::METHOD_POST;
        } else {
            $verb = Request::METHOD_PUT;
            $url .= '/' . rawurlencode($condition['name'] ?? '');
        }

        $result = $this->_fetch($url, $verb, $condition);

        if (!$result) {
            throw new LocalizedException(__('Failed to create a REQUEST condition.'));
        }

        return $result;
    }

    /**
     * Creates a new condition
     * @param $version
     * @param array $condition
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function removeCondition($version, string $conditionName)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/condition/' . $conditionName;

        return $this->_fetch($url, Request::METHOD_DELETE);
    }

    /**
     * Gets the specified condition.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getCondition($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/condition/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Creates a new header
     *
     * @param $version
     * @param $condition
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createHeader($version, array $condition)
    {
        $checkIfExists = $this->getHeader($version, $condition['name']);
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/header';

        if ($checkIfExists === false) {
            $verb = Request::METHOD_POST;
        } else {
            $verb = Request::METHOD_PUT;
            $url .= '/' . rawurlencode($condition['name'] ?? '');
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
     * @throws LocalizedException
     */
    public function getHeader($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/header/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Creates a new Response Object.
     *
     * @param $version
     * @param array $response
     * @return bool $result
     * @throws LocalizedException
     */
    public function createResponse($version, array $response)
    {
        $checkIfExists = $this->getResponse($version, $response['name']);
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/response_object';
        if (!$checkIfExists) {
            $verb = Request::METHOD_POST;
        } else {
            $verb = Request::METHOD_PUT;
            $url .= '/' . rawurlencode($response['name'] ?? '');
        }

        $result = $this->_fetch($url, $verb, $response);

        return $result;
    }

    /**
     * Remove response object
     *
     * @param $version
     * @param string $responseName
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function removeResponse($version, string $responseName)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/response_object/' . $responseName;

        return $this->_fetch($url, Request::METHOD_DELETE);
    }

    /**
     * Gets the specified Response Object.
     *
     * @param string $version
     * @param string $name
     * @return bool|mixed $result
     * @throws LocalizedException
     */
    public function getResponse($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/response_object/' . rawurlencode($name);
        $result = $this->_fetch($url, Request::METHOD_GET);

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
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/request_settings';
        if (!$checkIfExists) {
            $verb = Request::METHOD_POST;
        } else {
            $verb = Request::METHOD_PUT;
            $url .= '/' . rawurlencode($request['name'] ?? '');
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
     * @throws LocalizedException
     */
    public function getRequest($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/request_settings/' . rawurlencode($name);
        $result = $this->_fetch($url, Request::METHOD_GET, '', false, null, false);

        return $result;
    }

    /**
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getAllConditions($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/condition';
        $result = $this->_fetch($url, Request::METHOD_GET, '', false, null, false);

        return $result;
    }

    /**
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getAllDomains($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/domain';
        $result = $this->_fetch($url, Request::METHOD_GET, '', false, null, false);

        return $result;
    }

    /**
     * @param $version
     * @param $name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function deleteDomain($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/domain/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        return $result;
    }

    /**
     * @param $version
     * @param $data
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createDomain($version, $data)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/domain';
        $result = $this->_fetch($url, Request::METHOD_POST, $data);

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
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/request_settings/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        if (!$result) {
            throw new LocalizedException(__('Failed to delete the REQUEST object.'));
        }
    }

    /**
     * List all backends for a particular service and version.
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getBackends($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/backend';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Configure Backend settings
     *
     * @param $params
     * @param $version
     * @param $old_name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function configureBackend($params, $version, $old_name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/backend/' . rawurlencode($old_name);
        $result = $this->_fetch($url, Request::METHOD_PUT, $params);

        return $result;
    }

    /**
     * @param $params
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createBackend($params, $version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/backend';
        $result = $this->_fetch($url, Request::METHOD_POST, $params);

        return $result;
    }

    /**
     * @param $name
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function deleteBackend($name, $version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/backend/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        return $result;
    }

    /**
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getAllLogEndpoints($version)
    {
        $providers = $this->helper->getAvailableLogEndpointProviders();
        $results = [];
        foreach ($providers as $type => $providerName) {
            $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/logging/' . rawurlencode($type);
            $endpoints = $this->_fetch($url, Request::METHOD_GET);
            foreach ($endpoints as $endpoint) {
                $results[] = [
                    'label' => "{$endpoint->name} [{$providerName}]",
                    'name' => $endpoint->name,
                    'type' => $type,
                ];
            }
        }
        return $results;
    }

    /**
     * @param $version
     * @param $type
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getLogEndpoints($version, $type)
    {
        $results = [];
        $providers = $this->helper->getAvailableLogEndpointProviders();
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/logging/' . rawurlencode($type);
        $endpoints = $this->_fetch($url, Request::METHOD_GET);
        foreach ($endpoints as $endpoint) {
            $results[] = [
                'label' => "{$endpoint->name} [{$providers[$type]}]",
                'name' => $endpoint->name,
                'type' => $type,
            ];
        }
        return $results;
    }

    /**
     * @param $version
     * @param $type
     * @param $name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getLogEndpoint($version, $type, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/logging/' . rawurlencode($type ?? '') . '/' . rawurlencode($name ?? '');
        return $this->_fetch($url, Request::METHOD_GET);
    }

    /**
     * @param $version
     * @param $type
     * @param $params
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createLogEndpoint($version, $type, $params)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/logging/' . rawurlencode($type ?? '');
        return $this->_fetch($url, Request::METHOD_POST, $params);
    }

    /**
     * @param $version
     * @param $type
     * @param $params
     * @param $oldName
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function updateLogEndpoint($version, $type, $params, $oldName)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/logging/' . rawurlencode($type ?? '') . '/' . rawurlencode($oldName ?? '');
        return $this->_fetch($url, Request::METHOD_PUT, $params);
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
        $currentUsername = 'System';
        try {
            if ($this->state->getAreaCode() == 'adminhtml') {
                $getUser = $this->authSession->getUser();
                if (!empty($getUser)) {
                    $currentUsername = $getUser->getUserName();
                }
            }
        } catch (\Exception $e) {
            $this->log->log(100, 'Failed to retrieve Area Code');
        }

        $storeName = $this->helper->getStoreName();
        $storeUrl = $this->helper->getStoreUrl();

        $text =  $messagePrefix . ' user=' . $currentUsername . ' ' . $message . ' on <' . $storeUrl . '|Store> | ' . $storeName;

        $headers = $this->headersFactory->create();
        $headers->addHeaderLine('Content-type: application/json');

        $body = json_encode([
            "text"  =>  $text,
            "username" => "fastly-magento-bot",
            "icon_emoji"=> ":airplane:"
        ]);

        $client = $this->clientFactory->create();
        $request = $this->requestFactory->create();
        $client->setOptions([
            'timeout'      => 2,
            'httpversion' => '1.1'
        ]);
        $request->setUri($url);
        $request->setMethod(Request::METHOD_POST);
        $request->setHeaders($headers);
        $request->setContent($body);
        try {
            $response = $client->send($request);
            if ($response->getStatusCode() != 200) {
                $this->log->log(100, 'Failed to send message to the following Webhook: ' . $url);
            }
        } catch (\Exception $e) {
            $this->log->log(100, 'Failed to send message to the following Webhook: ' . $url);
        }
    }

    /**
     * Create named dictionary for a particular service and version.
     *
     * @param $version
     * @param $params
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createDictionary($version, $params)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/dictionary';
        $result = $this->_fetch($url, Request::METHOD_POST, $params);

        return $result;
    }

    /**
     * Delete named dictionary for a particular service and version.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function deleteDictionary($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/dictionary/' . rawurlencode($name ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        return $result;
    }

    /**
     * Get dictionary item list
     *
     * @param $dictionaryId
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function dictionaryItemsList($dictionaryId)
    {
        $url = $this->_getApiServiceUri() . 'dictionary/' . rawurlencode($dictionaryId ?? '') . '/items';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Fetches dictionary by name
     *
     * @param $version
     * @param $dictionaryName
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getSingleDictionary($version, $dictionaryName)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/dictionary/' . rawurlencode($dictionaryName ?? '');
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Get auth dictionary
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getAuthDictionary($version)
    {
        $name = Config::AUTH_DICTIONARY_NAME;
        $dictionary = $this->getSingleDictionary($version, $name);

        return $dictionary;
    }

    /**
     * Check if authentication dictionary is populated
     *
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

    /**
     * Create dictionary items
     *
     * @param $dictionaryId
     * @param $params
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createDictionaryItems($dictionaryId, $params)
    {
        $url = $this->_getApiServiceUri() . 'dictionary/' . rawurlencode($dictionaryId ?? '') . '/items';
        $result = $this->_fetch($url, Request::METHOD_PATCH, $params);

        return $result;
    }

    /**
     * List all dictionaries for the version of the service.
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getDictionaries($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/dictionary';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Delete single Dictionary item
     *
     * @param $dictionaryId
     * @param $itemKey
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function deleteDictionaryItem($dictionaryId, $itemKey)
    {
        $url = $this->_getApiServiceUri() . 'dictionary/' . rawurlencode($dictionaryId ?? '') . '/item/' . rawurlencode($itemKey ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        return $result;
    }

    /**
     * Upsert single Dictionary item
     *
     * @param $dictionaryId
     * @param $itemKey
     * @param $itemValue
     * @throws LocalizedException
     */
    public function upsertDictionaryItem($dictionaryId, $itemKey, $itemValue)
    {
        $body = ['item_value' => $itemValue];
        $url = $this->_getApiServiceUri() . 'dictionary/' . rawurlencode($dictionaryId ?? '') . '/item/' . rawurlencode($itemKey ?? '');
        $result = $this->_fetch($url, Request::METHOD_PUT, $body);

        if (!$result) {
            throw new LocalizedException(__('Failed to create Dictionary item.'));
        }
    }

    /**
     * Upsert multiple Dictionary items. Do not try to send more than 100 items at a time.
     *
     * @param $dictionaryId
     * @param array|object $items
     * @throws LocalizedException
     */
    public function upsertDictionaryItems($dictionaryId, $items)
    {
        foreach (array_chunk($items, self::UPSERT_ITEMS_MAX_COUNT) as $chunk) {
            $apiItems = [];
            foreach ($chunk as $item) {
                if (is_object($item)) {
                    $itemKey = $item->item_key;
                    $itemValue = $item->item_value;
                } else if (is_array($item) && isset($item['item_key'], $item['item_value'])) {
                    $itemKey = $item['item_key'];
                    $itemValue = $item['item_value'];
                } else {
                    continue;
                }

                $apiItems[] = [
                    'op' => 'upsert',
                    'item_key' => $itemKey,
                    'item_value' => $itemValue,
                ];
            }

            $url = $this->_getApiServiceUri() . 'dictionary/' . rawurlencode($dictionaryId ?? '') . '/items';
            $body = [
                'items' => $apiItems
            ];

            $result = $this->_fetch($url, Request::METHOD_PATCH, \json_encode($body));

            if (!$result) {
                if ($this->errorMessage) {
                    throw new LocalizedException(
                        __('Failed to upsert Dictionary items: %1', $this->errorMessage)
                    );
                }
                throw new LocalizedException(
                    __('Failed to upsert Dictionary items')
                );
            }
        }
    }

    /**
     * Get ACL container info
     * @param $version
     * @param $acl
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getSingleAcl($version, $acl)
    {
        $url = $this->_getApiServiceUri() . 'version/' . $version . '/acl/' . $acl;
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Create named ACL for a particular service and version.
     *
     * @param $version
     * @param $params
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function createAcl($version, $params)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/acl';
        $result = $this->_fetch($url, Request::METHOD_POST, $params);

        return $result;
    }

    /**
     * Fetch ACL list for particular service and version
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getAcls($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/acl';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Delete named ACL for a particular service and version.
     *
     * @param $version
     * @param $name
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function deleteAcl($version, $name)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/acl/' . rawurlencode($name);
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        return $result;
    }

    /**
     * Fetch ACL entry list for particular ACL
     *
     * @param $aclId
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function aclItemsList($aclId)
    {
        $url = $this->_getApiServiceUri() . 'acl/' . rawurlencode($aclId ?? '') . '/entries';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Upsert single ACL entry
     *
     * @param $aclId
     * @param $itemValue
     * @param $negated
     * @param string $comment
     * @param bool $subnet
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function upsertAclItem($aclId, $itemValue, $negated, $comment = 'Added by Magento Module', $subnet = false)
    {
        $body = [
            'ip' => $itemValue,
            'negated' => $negated,
            'comment' => $comment
        ];

        if ($subnet) {
            $body['subnet'] = $subnet;
        }

        $url = $this->_getApiServiceUri() . 'acl/' . rawurlencode($aclId ?? '') . '/entry';
        $result = $this->_fetch($url, Request::METHOD_POST, $body);

        return $result;
    }

    /**
     * Delete single ACL entry from specific ACL
     *
     * @param $aclId
     * @param $aclItemId
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function deleteAclItem($aclId, $aclItemId)
    {
        $url = $this->_getApiServiceUri() . 'acl/' . rawurlencode($aclId ?? '') . '/entry/' . rawurlencode($aclItemId ?? '');
        $result = $this->_fetch($url, Request::METHOD_DELETE);

        return $result;
    }

    public function bulkAclItems($aclId, $aclItems)
    {
        $url = $this->_getApiServiceUri() . 'acl/' . rawurlencode($aclId ?? '') . '/entries' ;

        // per documentation, maximum payload for bulk API is 1000
        $chunkedItems = array_chunk($aclItems, 1000);

        foreach ($chunkedItems as $items) {
            $payload['entries'] = $items;

            $this->_fetch($url, Request::METHOD_PATCH, json_encode($payload));
        }
    }

    /**
     * Update single ACL entry
     *
     * @param $aclId
     * @param $aclItemId
     * @param $itemValue
     * @param $negated
     * @param string $comment
     * @param bool $subnet
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function updateAclItem($aclId, $aclItemId, $itemValue, $negated, $comment = '', $subnet = false)
    {
        $body = [
            'ip' => $itemValue,
            'negated' => $negated,
            'comment' => $comment
        ];

        if ($subnet) {
            $body['subnet'] = $subnet;
        }

        $url = $this->_getApiServiceUri() . 'acl/' . rawurlencode($aclId ?? '') . '/entry/' . rawurlencode($aclItemId ?? '');
        $result = $this->_fetch($url, Request::METHOD_PATCH, json_encode($body));

        return $result;
    }

    /**
     * Query for historic stats
     *
     * @param array $parameters
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function queryHistoricStats(array $parameters)
    {
        $uri = $this->_getHistoricalEndpoint()
            . '?region=' . rawurlencode($parameters['region'] ?? '')
            . '&from=' . rawurlencode($parameters['from'] ?? '')
            . '&to=' . rawurlencode($parameters['to'] ?? '')
            . '&by=' . rawurlencode($parameters['sample_rate'] ?? '');

        $result = $this->_fetch($uri);

        return $result;
    }

    /**
     * method that fetches a VCL for specific version id
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getGeneratedVcl($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/generated_vcl';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    public function getParticularVersion($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '');
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Check if image optimization is enabled for the Fastly service
     *
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function checkImageOptimizationStatus()
    {
        $url = $this->_getApiServiceUri() . 'dynamic_io_settings';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Get the image optimization default config options
     *
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getImageOptimizationDefaultConfigOptions($version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/io_settings';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Configure the image optimization default config options
     *
     * @param $params
     * @param $version
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function configureImageOptimizationDefaultConfigOptions($params, $version)
    {
        $url = $this->_getApiServiceUri() . 'version/' . rawurlencode($version ?? '') . '/io_settings';
        $result = $this->_fetch($url, Request::METHOD_PATCH, $params);

        return $result;
    }

    /**
     * Retrieve Fastly service details
     *
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getServiceDetails()
    {
        if (!$this->config->isServiceConfigured()) {
            throw new LocalizedException(__('Fastly service is not configured.'));
        }

        $url = $this->_getApiServiceUri() . 'details';
        $result = $this->_fetch($url, Request::METHOD_GET);

        return $result;
    }

    /**
     * Retrieve Web Application Firewall settings
     *
     * @param string $id
     * @param bool $includeWafFirewallVersions
     * @return bool|mixed
     * @throws LocalizedException
     */
    public function getWafSettings(string $id, bool $includeWafFirewallVersions = false)
    {
        $url = $this->_getWafEndpoint() . rawurlencode($id ?? '');
        if ($includeWafFirewallVersions) {
            $url .= '?include=waf_firewall_versions';
        }

        return $this->_fetch($url, Request::METHOD_GET);
    }

    public function getLastErrorMessage()
    {
        return $this->errorMessage;
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
     * @throws LocalizedException
     */
    private function _fetch(
        $uri,
        $method = Request::METHOD_GET,
        $body = '',
        $test = false,
        $testApiKey = null,
        $logError = true
    ) {
        $apiKey = ($test == true) ? $testApiKey : $this->config->getApiKey();

        $headers = $this->headersFactory->create();
        $request = $this->requestFactory->create();

        // Correctly format $body string
        if (is_array($body) == true) {
            $body = http_build_query($body);
        }

        // Client headers
        $headers->addHeaderLine(self::FASTLY_HEADER_AUTH . ': ' . $apiKey);
        $headers->addHeaderLine('Accept: application/json');

        // Request method specific header & option changes
        switch ($method) {
            case Request::METHOD_PUT:
                $headers->addHeaderLine('Content-Type: application/x-www-form-urlencoded');
                break;
            case Request::METHOD_PATCH:
                $headers->addHeaderLine('Content-Type: text/json');
                break;
        }

        /** @var \Laminas\Http\Client $client */
        $client = $this->clientFactory->create();
        $client->setOptions([
            'httpversion' => '1.1'
        ]);
        $request->setMethod($method);
        $request->setUri($uri);
        $request->setHeaders($headers);
        if ($body) {
            $request->setContent($body);
        }
        $response = $client->send($request);
        // Parse response
        $responseBody = $response->getBody();
        $responseCode = $response->getStatusCode();
        $responseMessage = $response->getReasonPhrase();

        // Return error based on response code
        if ($responseCode == '429') {
            throw new LocalizedException(__($responseMessage));
        } elseif ($responseCode != '200') {
            if ($logError == true) {
                $this->logger->critical('Return status ' . $responseCode, $uri);
            }
            $errorDetails = $this->extractErrorDetails($responseBody, $responseMessage);
            $this->errorMessage = $errorDetails;
            return false;
        }

        return json_decode($responseBody);
    }

    private function stackTrace($type)
    {
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = [];
        foreach ($stackTrace as $row => $data) {
            if (!array_key_exists('file', $data) || !array_key_exists('line', $data)) {
                $trace[] = "# <unknown>";
            } else {
                $trace[] = "#{$row} {$data['file']}:{$data['line']} -> {$data['function']}()";
            }
        }

        $this->sendWebHook('*' . $type . ' backtrace:*```' . implode("\n", $trace) . '```');
    }

    /**
     * @param $responseBody
     * @param $responseMessage
     * @return string
     */
    private function extractErrorDetails($responseBody, $responseMessage)
    {
        if ($responseBody) {
            $decodedBody = json_decode($responseBody);
            if (isset($decodedBody->detail)) {
                return $responseMessage . ': ' . $decodedBody->detail;
            }
        }
        return $responseMessage;
    }
}
