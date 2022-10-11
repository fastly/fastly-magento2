<?php
declare(strict_types=1);

namespace Fastly\Cdn\Plugin\MediaStorage\App;

use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\MediaStorage\Model\File\Storage\Response;
use Magento\MediaStorage\App\Media;
use Fastly\Cdn\Model\Config;

/**
 * Class AroundMedia for disabling image resize if image optimization is enabled
 */
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
     * @var PlaceholderFactory
     */
    private $placeholderFactory;

    /**
     * @var State
     */
    private $appState;

    /**
     * AroundMedia constructor.
     *
     * @param Config $config
     * @param Response $response
     * @param PlaceholderFactory $placeholderFactory
     * @param State $state
     */
    public function __construct(
        Config $config,
        Response $response,
        PlaceholderFactory $placeholderFactory,
        State $state
    ) {
        $this->config = $config;
        $this->response = $response;
        $this->placeholderFactory = $placeholderFactory;
        $this->appState = $state;
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
        $this->appState->setAreaCode(Area::AREA_GLOBAL);
        $placeholder = $this->placeholderFactory->create(['type' => 'image']);
        $this->response->setFilePath($placeholder->getPath());
        return $this->response;
    }
}
