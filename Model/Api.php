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
     * Constructor
     *
     * @param Config $config, $config
     * @param CurlFactory $curlFactory
     * @param InvalidateLogger $logger
     */
    public function __construct(
        Config $config,
        CurlFactory $curlFactory,
        InvalidateLogger $logger
    ) {
        $this->config = $config;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
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
        // create purge token
        $expiration   = time() + self::PURGE_TOKEN_LIFETIME;
        $stringToSign = parse_url($uri, PHP_URL_PATH) . $expiration;
        $signature    = hash_hmac('sha1', $stringToSign, $this->config->getServiceId());
        $token        = $expiration . '_' . urlencode($signature);

        // set headers
        $headers = [
            self::FASTLY_HEADER_AUTH  . ': ' . $this->config->getApiKey(),
            self::FASTLY_HEADER_TOKEN . ': ' . $token
        ];

        // soft purge if needed
        if ($this->config->canUseSoftPurge()) {
            array_push( $headers, self::FASTLY_HEADER_SOFT_PURGE . ': 1' );
        }

        try {
            $client = $this->curlFactory->create();
            $client->setConfig(['timeout' => self::PURGE_TIMEOUT]);
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
}
