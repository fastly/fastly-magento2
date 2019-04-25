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
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

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
    const FASTLY_CRAWLER_TAG = 'fastly_crawler_protection_';

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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * FrontControllerPlugin constructor.
     * @param Request $request
     * @param Config $config
     * @param CacheInterface $cache
     * @param DateTime $coreDate
     * @param Response $response
     * @param Header $httpHeader
     * @param Filesystem $filesystem
     */
    public function __construct(
        Request $request,
        Config $config,
        CacheInterface $cache,
        DateTime $coreDate,
        Response $response,
        Header $httpHeader,
        Filesystem $filesystem
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->httpHeader = $httpHeader;
        $this->config = $config;
        $this->cache = $cache;
        $this->coreDate = $coreDate;
        $this->filesystem = $filesystem;
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
            if (preg_match('{' . $value->path . '}i', $path) == 1) {
                $limit = true;
            }
        }

        if ($limit) {
            $rateLimitingLimit = $this->config->getRateLimitingLimit();
            $rateLimitingTtl = $this->config->getRateLimitingTtl();
            $ip = $this->request->getServerValue('HTTP_FASTLY_CLIENT_IP') ?? $this->request->getClientIp();
            $tag = self::FASTLY_CACHE_TAG . $ip;
            $data = json_decode($this->cache->load($tag), true);

            return $this->processData($data, $tag, $rateLimitingTtl, $rateLimitingLimit, "path");
        }

        return false;
    }

    /**
     * @param $path
     * @return bool
     */
    private function crawlerProtection($path)
    {
        $ip = $this->request->getServerValue('HTTP_FASTLY_CLIENT_IP') ?? $this->request->getClientIp();

        if ($this->config->isExemptGoodBotsEnabled()) {
            if ($this->verifyBots($ip)) {
                return false;
            }
        }

        if ($this->readMaintenanceIp($ip)) {
            return false;
        }

        $pattern = '{^/(pub|var)/(static|view_preprocessed)/}';

        if (preg_match($pattern, $path) == 1) {
            return false;
        }

        $crawlerRateLimitingLimit = $this->config->getCrawlerRateLimitingLimit();
        $crawlerRateLimitingTtl = $this->config->getCrawlerRateLimitingTtl();
        $tag = self::FASTLY_CRAWLER_TAG . $ip;
        $data = json_decode($this->cache->load($tag), true);

        return $this->processData($data, $tag, $crawlerRateLimitingTtl, $crawlerRateLimitingLimit, "crawler");
    }

    /**
     * @param $data
     * @param $tag
     * @param $ttl
     * @param $limit
     * @param $limitingType - path or crawler
     * @return bool
     */
    private function processData($data, $tag, $ttl, $limit, $limitingType = "path")
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
                if ($limitingType == "path") {
                    $this->response->setHeader('Surrogate-Control', 'max-age=' . $ttl);
                }
                if ($limitingType == "crawler") {
                    $this->response->setHeader('Fastly-Vary', 'Fastly-Client-IP');
                }
                $this->response->setBody('<h1>Request limit exceeded</h1>');
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

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return false;
        }
        return (substr($haystack, -$length) === $needle);
    }

    /**
     * @param $ip
     * @return bool
     */
    private function verifyBots($ip)
    {
        $userAgent = strtolower($this->httpHeader->getHttpUserAgent());
        $goodBots = [
            'googlebot' => [
                'googlebot.com',
                'google.com'
            ],
            'msnbot'    => [
                'search.msn.com'
            ],
            'bingbot'   => [
                'search.msn.com'
            ]
        ];

        // for each good bot
        foreach ($goodBots as $botName => $botDomains) {
            // check if the user agent is a bot
            if (strpos($userAgent, $botName) !== false) {
                // get domain from ip value
                $domain = gethostbyaddr($ip);
                // for each verified bot domain
                foreach ($botDomains as $botDomain) {
                    // check if the verified and retrieved domains match
                    $endsWith = $this->endsWith($domain, $botDomain);
                    // if the domain is verified
                    if ($endsWith !== false) {
                        // confirm IP address
                        $addr = gethostbyname($domain);
                        if ($ip == $addr) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    private function readMaintenanceIp($ip)
    {
        $flagDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        if ($flagDir->isExist('.maintenance.ip')) {
            $temp = $flagDir->readFile('.maintenance.ip');
            $tempList = explode(',', trim($temp));
            foreach ($tempList as $key => $value) {
                if (!empty($value) && trim($value) == $ip) {
                    return true;
                }
            }
        }
        return false;
    }
}
