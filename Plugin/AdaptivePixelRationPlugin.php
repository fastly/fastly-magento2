<?php

namespace Fastly\Cdn\Plugin;

/**
 * Class AdaptivePixelRationPlugin
 * @package Fastly\Cdn\Plugin
 */
class AdaptivePixelRationPlugin
{
    /**
     * @var \Fastly\Cdn\Model\Config
     */
    public $config;

    /**
     * AdaptivePixelRationPlugin constructor.
     *
     * @param \Fastly\Cdn\Model\Config $config
     */
    public function __construct(\Fastly\Cdn\Model\Config $config)
    {
        $this->config = $config;
    }

    /**
     * Adjust srcset if required
     *
     * @param \Magento\Catalog\Block\Product\Image $subject
     */
    public function beforeToHtml(\Magento\Catalog\Block\Product\Image $subject)
    {
        if ($this->config->isImageOptimizationPixelRatioEnabled() !== true) {
            return;
        }

        $imageUrl = $subject->getData('image_url');
        $glue = (strpos($imageUrl, '?') !== false) ? '&' : '?';
        # Pixel ratios defaults are based on the table from https://mydevice.io/devices/
        # Bulk of devices are 2x however many new devices like Samsung S8, iPhone X etc are 3x and 4x
        $srcSet = [
            $imageUrl . $glue . 'dpr=2 2x',
            $imageUrl . $glue . 'dpr=3 3x',
            $imageUrl . $glue . 'dpr=4 4x'
        ];

        $subject->setData('custom_attributes', 'srcset="' . implode(',', $srcSet) . '"');
    }
}
