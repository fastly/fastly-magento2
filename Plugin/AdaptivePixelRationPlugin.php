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
namespace Fastly\Cdn\Plugin;

use Fastly\Cdn\Model\Config;
use Magento\Catalog\Block\Product\Image;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class AdaptivePixelRationPlugin
 *
 * @package Fastly\Cdn\Plugin
 */
class AdaptivePixelRationPlugin
{
    /**
     * @var Config
     */
    public $config;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * AdaptivePixelRationPlugin constructor.
     *
     * @param Config $config
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        Config $config,
        ProductMetadataInterface $productMetadata
    ) {
        $this->config = $config;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Adjust srcset if required
     *
     * @param Image $subject
     */
    public function beforeToHtml(Image $subject)
    {
        if ($this->config->isImageOptimizationPixelRatioEnabled() !== true) {
            return;
        }

        $srcSet = [];
        $imageUrl = $subject->getData('image_url');
        $pixelRatios = $this->config->getImageOptimizationRatios();
        $pixelRatiosArray = explode(',', $pixelRatios);
        $glue = (strpos($imageUrl, '?') !== false) ? '&' : '?';

        # Pixel ratios defaults are based on the table from https://mydevice.io/devices/
        # Bulk of devices are 2x however many new devices like Samsung S8, iPhone X etc are 3x and 4x
        foreach ($pixelRatiosArray as $pr) {
            $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';
            $srcSet[] = $imageUrl . $glue . $ratio;
        }

        if (version_compare($this->productMetadata->getVersion(), '2.4', '<')) {
            $subject->setData('custom_attributes', 'srcset="' . implode(',', $srcSet) . '"');
        } else {
            $customAttributes = $subject->getCustomAttributes() ?: [];
            $customAttributes['srcset'] = implode(',', $srcSet);
            $subject->setData('custom_attributes', $customAttributes);
        }
    }
}
