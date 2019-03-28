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

    /** @var int Number of tolerated rate limit requests */
    const FASTLY_RATE_LIMIT = 10;

    /** @var int Rate limit lifetime */
    const FASTLY_RATE_LIMIT_LIFETIME = 3600;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var DateTime
     */
    protected $coreDate;

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
    public function aroundDispatch(FrontController $subject, callable $proceed, ...$args)
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

        $limitedPaths = [
            '/paypal/transparent/requestsecuretoken',
        ];

        $limit = false;
        foreach ($limitedPaths as $limited) {
            if (strpos($path, $limited) !== false) {
                $limit = true;
            }
        }

        if ($limit) {
            $ip = $request->getServerValue('HTTP_FASTLY_CLIENT_IP') ?? $request->getClientIp();
            $tag = self::FASTLY_CACHE_TAG . $path . '_' . $ip;
            $data = json_decode($this->cache->load($tag), true);

            if (empty($data)) {
                $date = $this->coreDate->timestamp();
                $data = json_encode([
                    'usage' => 1,
                    'date' => $date
                ]);

                $this->cache->save($data, $tag, [], self::FASTLY_RATE_LIMIT_LIFETIME);

            } else {
                $usage = $data['usage'] ?? 0;
                $date = $data['date'] ?? null;
                $newDate = $this->coreDate->timestamp();
                $dateDiff = ($newDate - $date);

                if ($dateDiff >= self::FASTLY_RATE_LIMIT_LIFETIME) {
                    $this->cache->remove($tag);
                    $usage = 0;
                }

                if ($usage >= self::FASTLY_RATE_LIMIT) {
                    $response->setStatusHeader(429, null, 'API limit exceeded');
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
