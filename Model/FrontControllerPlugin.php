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

/**
 * Class FrontControllerPlugin
 *
 * @package Fastly\Cdn\Model
 */
class FrontControllerPlugin
{
    /** @var string Cache tag for storing rate limit data */
    const FASTLY_CACHE_TAG = 'fastly_rate_limit_';

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
     * FrontControllerPlugin constructor.
     * @param Request $request
     * @param \Fastly\Cdn\Model\Config $config
     * @param CacheInterface $cache
     * @param DateTime $coreDate
     * @param \Magento\Framework\App\ResponseInterface $response
     */
    public function __construct(
        Request $request,
        Config $config,
        CacheInterface $cache,
        DateTime $coreDate,
        Response $response
    ) {
        $this->request = $request;
        $this->response = $response;
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
        if (!$this->config->isRateLimitingEnabled()) {
            return $proceed(...$args);
        }

        /** @var $subject \Magento\Framework\App\FrontController */
        /** @var $request \Magento\Framework\App\Request\Http */
        /** @var $response \Magento\Framework\App\Response\Http */
        $request = $this->request;
        $response = $this->response;
        $path = strtolower($request->getPathInfo());

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
            $ip = $request->getServerValue('HTTP_FASTLY_CLIENT_IP') ?? $request->getClientIp();
            $tag = self::FASTLY_CACHE_TAG . $ip;
            $data = json_decode($this->cache->load($tag), true);

            if (empty($data)) {
                $date = $this->coreDate->timestamp();
                $data = json_encode([
                    'usage' => 1,
                    'date'  => $date
                ]);

                $this->cache->save($data, $tag, [], $rateLimitingTtl);
            } else {
                $usage = $data['usage'] ?? 0;
                $date = $data['date'] ?? null;
                $newDate = $this->coreDate->timestamp();
                $dateDiff = ($newDate - $date);

                if ($dateDiff >= $rateLimitingTtl) {
                    $data = json_encode([
                        'usage' => 1,
                        'date'  => $newDate
                    ]);
                    $this->cache->save($data, $tag, [], $rateLimitingTtl);
                    return $proceed(...$args);
                }

                if ($usage >= $rateLimitingLimit) {
                    $response->setStatusHeader(429, null, 'API limit exceeded');
                    $response->SetBody("Request limit exceeded");
                    $response->setNoCacheHeaders();
                    return $response;
                } else {
                    $usage++;
                    $data['usage'] = $usage;
                    $this->cache->save(json_encode($data), $tag, []);
                }
            }
        }

        return $proceed(...$args);
    }
}
