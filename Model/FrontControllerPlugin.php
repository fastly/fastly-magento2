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
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\App\ResponseInterface as Response;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Class FrontControllerPlugin
 *
 * @package Fastly\Cdn\Model
 */
class FrontControllerPlugin
{
    /** @var string Cache tag for storing rate limit data */
    const FASTLY_CACHE_TAG = 'fastly_rl_sensitive_path__';
    /** @var string Cache tag for storing crawler rate limit data */
    const FASTLY_CRAWLER_TAG = 'fastly_rl_crawler_protection_';
    /** @var string Cache tag for storing maintenance ip file data */
    const FASTLY_CACHE_MAINTENANCE_IP_FILE_TAG = 'fastly_rl_maintenance_ip_file';

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var DateTime
     */
    private $coreDate;

    /**
     * @var Config
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FrontControllerPlugin constructor.
     * @param Request $request
     * @param Config $config
     * @param CacheInterface $cache
     * @param DateTime $coreDate
     * @param Response $response
     * @param Header $httpHeader
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Request $request,
        Config $config,
        CacheInterface $cache,
        DateTime $coreDate,
        Response $response,
        Header $httpHeader,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->httpHeader = $httpHeader;
        $this->config = $config;
        $this->cache = $cache;
        $this->coreDate = $coreDate;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    /**
     * Check if request is limited
     * @param FrontControllerInterface $subject
     * @param callable $proceed
     * @param mixed ...$args
     * @return \Magento\Framework\App\Response\Http|\Magento\Framework\App\ResponseInterface
     */
    public function aroundDispatch(FrontControllerInterface $subject, callable $proceed, ...$args) // @codingStandardsIgnoreLine - unused parameter
    {
        if (!$this->config->isFastlyEnabled()) {
            return $proceed(...$args);
        }

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
        $ip = $this->request->getServerValue('HTTP_FASTLY_CLIENT_IP') ?? $this->request->getClientIp();

        if ($this->readMaintenanceIp($ip)) {
            return false;
        }

        $limitedPaths = json_decode($this->config->getRateLimitPaths());
        if (!$limitedPaths) {
            $limitedPaths = [];
        }

        $limit = false;
        foreach ($limitedPaths as $key => $value) {
            if (preg_match('{' . $value->path . '}i', $path) == 1) {
                $limit = true;
                break;
            }
        }

        if ($limit) {
            $rateLimitingLimit = $this->config->getRateLimitingLimit();
            $rateLimitingTtl = $this->config->getRateLimitingTtl();
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
            $this->log('First tag hit during a window. Starting the counter for: "' . $tag);
        } else {
            $usage = $data['usage'] ?? 0;
            $date = $data['date'] ?? null;
            $newDate = $this->coreDate->timestamp();
            $dateDiff = ($newDate - $date);
            $block_time = $ttl - $dateDiff;

            if ($dateDiff >= $ttl) {
                $data = json_encode([
                    'usage' => 1,
                    'date'  => $newDate
                ]);
                $this->cache->save($data, $tag, [], $ttl);
                $this->log('Reset count. Hit outside the enforcement window for: "' . $tag);
                return false;
            }

            if ($usage >= $limit) {
                $this->response->setStatusHeader(429, null, 'API limit exceeded');
                if ($limitingType == "path") {
                    # Only cache blocking decision for the remainder of the enforcement window
                    $this->response->setHeader('Surrogate-Control', 'max-age=' . $block_time);
                }
                if ($limitingType == "crawler") {
                    $this->response->setHeader('Fastly-Vary', 'Fastly-Client-IP');
                }
                $this->response->setBody('<h1>Request limit exceeded</h1>');
                $this->response->setNoCacheHeaders();
                $this->log('Rate limit exceeded: "' . $tag . '" Count: ' . $usage . '/' . $limit . ' Window length: ' . $dateDiff . ' secs/' . $ttl . ' Block issued lasting ' . $block_time . ' secs.');
                return true;
            } else {
                $usage++;
                $data['usage'] = $usage;
                $this->cache->save(json_encode($data), $tag, []);
                $this->log('Hit inside enforcement window: "' . $tag . '" Count: ' . $usage . '/' . $limit . ' Window length: ' . $dateDiff . ' secs/' . $ttl);
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
        $tag = self::FASTLY_CACHE_MAINTENANCE_IP_FILE_TAG;
        $data = json_decode($this->cache->load($tag));
        if (empty($data)) {
            $data = [];
            $flagDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            if ($flagDir->isExist('.maintenance.ip')) {
                $temp = $flagDir->readFile('.maintenance.ip');
                $data = explode(',', trim($temp));
                $this->cache->save(json_encode($data), $tag, []);
            }
        }

        foreach ($data as $key => $value) {
            if (!empty($value) && trim($value) == $ip) {
                return true;
            }
        }
        return false;
    }

    private function log($message)
    {
        if ($this->config->isRateLimitingLoggingEnabled()) {
            $this->logger->info($message);
        }
    }
}
