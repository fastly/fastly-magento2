<?php
declare(strict_types=1);

namespace Fastly\Cdn\Plugin\GraphQl;

use Fastly\Cdn\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Module\Manager;

class AfterRenderResult
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * AfterRenderResult constructor.
     *
     * @param Config $config
     * @param Manager $moduleManager
     */
    public function __construct(
        Config $config,
        Manager $moduleManager
    ) {
        $this->config = $config;
        $this->moduleManager = $moduleManager;
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

        if (!$this->config->isEnabled() || !($this->config->getType() === Config::FASTLY)) {
            return $result;
        }

        if (!$this->moduleManager->isEnabled('Magento_GraphQlCache') ||
            !ObjectManager::getInstance()->get(\Magento\GraphQlCache\Model\CacheableQuery::class)->isCacheable()
        ) {
            return $result;
        }

        $header = $response->getHeader('cache-control');

        if ($header) {
            if ($ttl = $this->config->getStaleTtl()) {
                $header->addDirective('stale-while-revalidate', $ttl);
            }

            if ($ttl = $this->config->getStaleErrorTtl()) {
                $header->addDirective('stale-if-error', $ttl);
            }
        }

        return $result;
    }
}
