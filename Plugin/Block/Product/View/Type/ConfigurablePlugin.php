<?php

namespace Fastly\Cdn\Plugin\Block\Product\View\Type;

use Fastly\Cdn\Model\AdaptivePixelRatio;
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
     * @var AdaptivePixelRatio
     */
    private $adaptivePixelRatio;

    /**
     * ConfigurablePlugin constructor.
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
     * @param Configurable $subject
     * @param string $result
     * @return bool|string
     */
    public function afterGetJsonConfig(Configurable $subject, string $result)
    {
        if (!$this->config->isImageOptimizationPixelRatioEnabled() || !$result)
            return $result;

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

                $image['fastly_srcset'] = $this->adaptivePixelRatio->generateSrcSet($image['img'], $pixelRatios);
            }
        }

        return $this->serializer->serialize($config);
    }
}
