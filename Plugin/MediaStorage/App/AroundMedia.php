<?php
declare(strict_types=1);

namespace Fastly\Cdn\Plugin\MediaStorage\App;

use Magento\MediaStorage\Model\File\Storage\Response;
use Magento\MediaStorage\App\Media;
use Fastly\Cdn\Model\Config;

class AroundMedia
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Response
     */
    private $response;

    /**
     * AroundMedia constructor.
     *
     * @param Config $config
     * @param Response $response
     */
    public function __construct(
        Config $config,
        Response $response
    ) {
        $this->config = $config;
        $this->response = $response;
    }

    /**
     * Disable image generation if image optimization is enabled
     *
     * @param Media $subject
     * @param callable $proceed
     * @return Response
     */
    public function aroundLaunch(Media $subject, callable $proceed)
    {
        if (!$this->config->isImageOptimizationEnabled()) {
            return $proceed();
        }

        $this->response->setStatusHeader(404, '1.1', 'Not Found');
        $this->response->setHeader('Status', '404 File not found');
        $this->response->setNoCacheHeaders();
        return $this->response;
    }
}
