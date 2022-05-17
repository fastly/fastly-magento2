<?php
/**
 * @author Domagoj Potkoc <domagoj@favicode.net>
 * @copyright Copyright (c) 2021 aescripts + aeplugins
 * @license Commercial
 */
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

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function aroundExecute(ImageResizeAfterProductSave $subject, callable $proceed, Observer $observer)
    {
        if ($this->config->isImageOptimizationEnabled()) {
            return;
        }
        $proceed($observer);
    }
}
