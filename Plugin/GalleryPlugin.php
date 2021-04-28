<?php

namespace Fastly\Cdn\Plugin;

use Fastly\Cdn\Model\AdaptivePixelRation;
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
     * @var AdaptivePixelRation
     */
    private $adaptivePixelRation;

    /**
     * GalleryPlugin constructor.
     * @param SerializerInterface $serializer
     * @param AdaptivePixelRation $adaptivePixelRation
     * @param Config $config
     */
    public function __construct(
        SerializerInterface $serializer,
        AdaptivePixelRation $adaptivePixelRation,
        Config $config
    ) {
        $this->serializer = $serializer;
        $this->config = $config;
        $this->adaptivePixelRation = $adaptivePixelRation;
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

           $image['srcset'] = $this->adaptivePixelRation->generateSrcSet($image['img'], $pixelRatios);
       }

       return $this->serializer->serialize($images);
   }
}
