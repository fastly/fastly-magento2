<?php
declare(strict_types=1);

namespace Fastly\Cdn\Plugin\GraphQl;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\GraphQlCache\Model\CacheableQuery;
use Fastly\Cdn\Model\Config;

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
     * AfterRenderResult constructor.
     *
     * @param Config $config
     * @param CacheableQuery $cacheableQuery
     */
    public function __construct(
        Config $config,
        CacheableQuery $cacheableQuery
    ) {
        $this->config = $config;
        $this->cacheableQuery = $cacheableQuery;
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
        if ($this->config->isEnabled()
            && $this->config->getType() === Config::FASTLY
            && $this->cacheableQuery->isCacheable()) {
            $header = $response->getHeader('cache-control');
            if ($header && $ttl = $this->config->getStaleTtl()) {
                $header->addDirective('stale-while-revalidate', $ttl);
            }
        }
        return $result;
    }
}
