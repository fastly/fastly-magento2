<?php

namespace Fastly\Cdn\Plugin;

use Fastly\Cdn\Model\AdaptivePixelRatio;
use Fastly\Cdn\Model\Config;
use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class GalleryPlugin
 * @package Fastly\Cdn\Plugin
 */
class GalleryPlugin
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var AdaptivePixelRatio
     */
    private $adaptivePixelRatio;

    /**
     * GalleryPlugin constructor.
     * @param SerializerInterface $serializer
     * @param AdaptivePixelRatio $adaptivePixelRatio
     * @param Config $config
     */
    public function __construct(
        SerializerInterface $serializer,
        AdaptivePixelRatio $adaptivePixelRatio,
        Config $config
    ) {
        $this->serializer = $serializer;
        $this->config = $config;
        $this->adaptivePixelRatio = $adaptivePixelRatio;
    }

    /**
     * @param Gallery $subject
     * @param string|false $result
     * @return false|string
     */
   public function afterGetGalleryImagesJson(Gallery $subject, $result)
   {
       if (!$this->config->isImageOptimizationPixelRatioEnabled() || !$result)
           return $result;

       if (!$images = $this->serializer->unserialize($result))
           return $result;

       if (!$pixelRatios = explode(',', $this->config->getImageOptimizationRatios()))
           return $result;

       foreach ($images as &$image) {
           if (!isset($image['img']))
               continue;

           $image['fastly_srcset'] = $this->adaptivePixelRatio->generateSrcSet($image['img'], $pixelRatios);
       }

       return $this->serializer->serialize($images);
   }
}
