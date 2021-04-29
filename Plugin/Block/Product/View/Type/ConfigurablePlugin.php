<?php

namespace Fastly\Cdn\Plugin\Block\Product\View\Type;

use Fastly\Cdn\Model\AdaptivePixelRation;
use Fastly\Cdn\Model\Config;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class ConfigurablePlugin
 * @package Fastly\Cdn\Plugin\Block\Product\View\Type
 */
class ConfigurablePlugin
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
     * ConfigurablePlugin constructor.
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
     * @param Configurable $subject
     * @param string $result
     * @return bool|string
     */
    public function afterGetJsonConfig(Configurable $subject, string $result)
    {
//        if (!$this->config->isImageOptimizationPixelRatioEnabled() || !$result)
//            return $result;

        if (!$config = $this->serializer->unserialize($result))
            return $result;

        if (!isset($config['images']))
            return $result;

        if (!$pixelRatios = explode(',', $this->config->getImageOptimizationRatios()))
            return $result;

        foreach ($config['images'] as &$images) {
            foreach ($images as &$image) {
                if (!isset($image['img']))
                    continue;

                $image['fastly_srcset'] = $this->adaptivePixelRation->generateSrcSet($image['img'], $pixelRatios);
            }
        }

        return $this->serializer->serialize($config);
    }
}
