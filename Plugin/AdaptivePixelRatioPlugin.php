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
 * Class AdaptivePixelRatioPlugin for image ration
 *
 */
class AdaptivePixelRatioPlugin
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
     * AdaptivePixelRatioPlugin constructor.
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
        $pixelRatiosArray = $this->config->getImageOptimizationRatios();
        $glue = (strpos($imageUrl ?? '', '?') !== false) ? '&' : '?';

        # Pixel ratios defaults are based on the table from https://mydevice.io/devices/
        # Bulk of devices are 2x however many new devices like Samsung S8, iPhone X etc are 3x and 4x
        foreach ($pixelRatiosArray as $pr) {
            $ratio = 'dpr=' . $pr . ' ' . $pr . 'x';
            $srcSet[] = $imageUrl . $glue . $ratio;
        }

        $srcSet = implode(',', $srcSet);
        $customAttributes = $subject->getCustomAttributes();
        if (version_compare((string)$this->productMetadata->getVersion(), '2.4', '<')) {
            $customAttributes = !empty($customAttributes) ? [$customAttributes] : [];
            $customAttributes[] = 'srcset="' . $srcSet . '"';
            $subject->setData('custom_attributes', implode(' ', $customAttributes));
        } else {
            $customAttributes = $customAttributes ?: [];
            $customAttributes['srcset'] = $srcSet;
            $subject->setData('custom_attributes', $customAttributes);
        }
    }
}
