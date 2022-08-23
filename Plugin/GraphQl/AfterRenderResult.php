<?php
declare(strict_types=1);

namespace Fastly\Cdn\Plugin\GraphQl;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\GraphQlCache\Model\CacheableQuery;
use Magento\PageCache\Model\Config;
use \Fastly\Cdn\Model\Config as FastlyConfig;

class AfterRenderResult
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheableQuery
     */
    private $cacheableQuery;

    /**
     * @var FastlyConfig
     */
    private $fastlyConfig;

    /**
     * AfterRenderResult constructor.
     *
     * @param Config $config
     * @param CacheableQuery $cacheableQuery
     * @param FastlyConfig $fastlyConfig
     */
    public function __construct(
        Config $config,
        CacheableQuery $cacheableQuery,
        FastlyConfig $fastlyConfig
    ) {
        $this->config = $config;
        $this->cacheableQuery = $cacheableQuery;
        $this->fastlyConfig = $fastlyConfig;
    }

    /**
     * Add header directive stale-while-revalidate
     *
     * @param ResultInterface $subject
     * @param ResultInterface $result
     * @param ResponseInterface $response
     * @return ResultInterface
     */
    public function afterRenderResult(
        ResultInterface $subject,
        ResultInterface $result,
        ResponseInterface $response
    ): ResultInterface {
        if ($this->config->isEnabled() && $this->cacheableQuery->isCacheable()) {
            $header = $response->getHeader('cache-control');
            if ($header && $ttl = $this->fastlyConfig->getStaleTtl()) {
                $header->addDirective('stale-while-revalidate', $ttl);
            }
        }
        return $result;
    }
}
