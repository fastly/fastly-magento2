<?php

namespace Fastly\Cdn\Plugin\Swatches\Helper\Data;

use Fastly\Cdn\Model\AdaptivePixelRatio;
use Fastly\Cdn\Model\Config;
use Magento\Catalog\Model\Product;
use Magento\Swatches\Helper\Data;

class AppendSrcsetInformationPlugin
{
    private $config;
    private $adaptivePixelRatio;

    private $pixelRatios = [];

    // \Magento\Swatches\Helper\Data::getAllSizeImages
    private const IMAGE_SIZES = [
        'large',
        'medium',
        'small',
    ];

    public function __construct(
        Config $config,
        AdaptivePixelRatio $adaptivePixelRatio
    ) {
        $this->config = $config;
        $this->adaptivePixelRatio = $adaptivePixelRatio;
    }

    public function afterGetProductMediaGallery(Data $subject, array $result, Product $product): array
    {
        if (empty($result) || !$this->isPixelRatioEnabled()) {
            return $result;
        }

        $result['fastly_srcset'] = $this->buildFastlySrcset($result);

        if (isset($result['gallery']) && is_array($result['gallery'])) {
            foreach ($result['gallery'] as &$galleryImg) {
                $galleryImg['fastly_srcset'] = $this->buildFastlySrcset($galleryImg);
            }
        }

        return $result;
    }

    private function buildFastlySrcset(array $images): array
    {
        $srcSets = [];

        foreach (self::IMAGE_SIZES as $size) {
            if (isset($images[$size]) && is_string($images[$size])) {
                $srcSets[$size] = $this->generateSrcSet($images[$size]);
            }
        }

        return $srcSets;
    }

    private function isPixelRatioEnabled(): bool
    {
        return $this->config->isImageOptimizationPixelRatioEnabled()
            && !empty($this->config->getImageOptimizationRatios());
    }

    private function generateSrcSet(string $url): string
    {
        $pixelRatios = $this->pixelRatios;
        if (empty($pixelRatios)) {
            $pixelRatios = $this->config->getImageOptimizationRatios();
            $this->pixelRatios = $pixelRatios;
        }

        return implode(', ', $this->adaptivePixelRatio->generateSrcSet($url, $pixelRatios));
    }
}
