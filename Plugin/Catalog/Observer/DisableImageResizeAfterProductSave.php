<?php
declare(strict_types=1);

namespace Fastly\Cdn\Plugin\Catalog\Observer;

use Magento\Catalog\Observer\ImageResizeAfterProductSave;
use Fastly\Cdn\Model\Config;
use Magento\Framework\Event\Observer;

class DisableImageResizeAfterProductSave
{
    /**
     * @var Config
     */
    private $config;

    /**
     * DisableImageResizeAfterProductSave constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Disable image resize if deep image optimization is enabled
     *
     * @param ImageResizeAfterProductSave $subject
     * @param callable $proceed
     * @param Observer $observer
     */
    public function aroundExecute(ImageResizeAfterProductSave $subject, callable $proceed, Observer $observer)
    {
        if ($this->config->isImageOptimizationEnabled()) {
            return;
        }
        $proceed($observer);
    }
}
