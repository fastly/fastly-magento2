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

        $srcSet = [];
        $imageUrl = $subject->getData('image_url');
        $pixelRatios = $this->config->getImageOptimizationRatios();
        $pixelRatiosArray = explode(',', $pixelRatios);
        $glue = (strpos($imageUrl, '?') !== false) ? '&' : '?';
        foreach ($pixelRatiosArray as $pr) {
            $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';
            $srcSet[] = $imageUrl . $glue . $ratio;
        }

        $subject->setData('custom_attributes', 'srcset="' . implode(',', $srcSet) . '"');
    }
}
