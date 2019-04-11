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

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\FrontController;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\App\ResponseInterface as Response;
use Magento\Framework\HTTP\Header;

/**
 * Class FrontControllerPlugin
 *
 * @package Fastly\Cdn\Model
 */
class FrontControllerPlugin
{
    /** @var string Cache tag for storing rate limit data */
    const FASTLY_CACHE_TAG = 'fastly_rate_limit_';
    /** @var string Cache tag for storing crawler rate limit data */
    const FASTLY_CRAWLER_TAG = 'fastly_crawler_protection';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var DateTime
     */
    private $coreDate;

    /**
     * @var \Magento\PageCache\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * FrontControllerPlugin constructor.
     * @param Request $request
     * @param Config $config
     * @param CacheInterface $cache
     * @param DateTime $coreDate
     * @param Response $response
     * @param Header $httpHeader
     */
    public function __construct(
        Request $request,
        Config $config,
        CacheInterface $cache,
        DateTime $coreDate,
        Response $response,
        Header $httpHeader
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->httpHeader = $httpHeader;
        $this->config = $config;
        $this->cache = $cache;
        $this->coreDate = $coreDate;
    }

    /**
     * Check if request is limited
     * @param FrontController $subject
     * @param callable $proceed
     * @param mixed ...$args
     * @return \Magento\Framework\App\Response\Http|\Magento\Framework\App\ResponseInterface
     */
    public function aroundDispatch(FrontController $subject, callable $proceed, ...$args) // @codingStandardsIgnoreLine - unused parameter
    {
        $isRateLimitingEnabled = $this->config->isRateLimitingEnabled();
        $isCrawlerProtectionEnabled = $this->config->isCrawlerProtectionEnabled();

        if (!$isRateLimitingEnabled && !$isCrawlerProtectionEnabled) {
            return $proceed(...$args);
        }

        $path = strtolower($this->request->getPathInfo());

        if ($isRateLimitingEnabled && $this->sensitivePathProtection($path)) {
            return $this->response;
        }

        if ($isCrawlerProtectionEnabled && $this->crawlerProtection($path)) {
            return $this->response;
        }

        return $proceed(...$args);
    }

    /**
     * @param $path
     * @return bool
     */
    private function sensitivePathProtection($path)
    {
        $limitedPaths = json_decode($this->config->getRateLimitPaths());
        if (!$limitedPaths) {
            $limitedPaths = [];
        }

        $limit = false;
        foreach ($limitedPaths as $key => $value) {
            if (preg_match('{' . $value->path . '}', $path) == 1) {
                $limit = true;
            }
        }

        if ($limit) {
            $rateLimitingLimit = $this->config->getRateLimitingLimit();
            $rateLimitingTtl = $this->config->getRateLimitingTtl();
            $this->response->setHeader('Surrogate-Control', 'max-age=' . $rateLimitingTtl);
            $ip = $this->request->getServerValue('HTTP_FASTLY_CLIENT_IP') ?? $this->request->getClientIp();
            $tag = self::FASTLY_CACHE_TAG . $ip;
            $data = json_decode($this->cache->load($tag), true);

            return $this->processData($data, $tag, $rateLimitingTtl, $rateLimitingLimit);
        }

        return false;
    }

    /**
     * @param $path
     * @return bool
     */
    private function crawlerProtection($path)
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        $crawler = \Zend_Http_UserAgent_Bot::match($userAgent, $_SERVER);

        if ($crawler) {
            $pattern = '{^/(pub|var)/(static|view_preprocessed)/}';

            if (preg_match($pattern, $path) == 1) {
                return false;
            }

            $crawlerRateLimitingLimit = $this->config->getCrawlerRateLimitingLimit();
            $crawlerRateLimitingTtl = $this->config->getCrawlerRateLimitingTtl();
            $tag = self::FASTLY_CRAWLER_TAG;
            $data = json_decode($this->cache->load($tag), true);

            return $this->processData($data, $tag, $crawlerRateLimitingTtl, $crawlerRateLimitingLimit);
        }
        return false;
    }

    /**
     * @param $data
     * @param $tag
     * @param $ttl
     * @param $limit
     * @return bool
     */
    private function processData($data, $tag, $ttl, $limit)
    {
        if (empty($data)) {
            $date = $this->coreDate->timestamp();
            $data = json_encode([
                'usage' => 1,
                'date'  => $date
            ]);
            $this->cache->save($data, $tag, [], $ttl);
        } else {
            $usage = $data['usage'] ?? 0;
            $date = $data['date'] ?? null;
            $newDate = $this->coreDate->timestamp();
            $dateDiff = ($newDate - $date);

            if ($dateDiff >= $ttl) {
                $data = json_encode([
                    'usage' => 1,
                    'date'  => $newDate
                ]);
                $this->cache->save($data, $tag, [], $ttl);
                return false;
            }

            if ($usage >= $limit) {
                $this->response->setStatusHeader(429, null, 'API limit exceeded');
                $this->response->setBody("Request limit exceeded");
                $this->response->setNoCacheHeaders();
                return true;
            } else {
                $usage++;
                $data['usage'] = $usage;
                $this->cache->save(json_encode($data), $tag, []);
            }
        }
        return false;
    }
}
