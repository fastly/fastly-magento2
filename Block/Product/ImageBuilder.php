<?php

namespace Fastly\Cdn\Block\Product;

use Magento\Catalog\Helper\ImageFactory as HelperFactory;
use Magento\Catalog\Block\Product\ImageFactory;

class ImageBuilder extends \Magento\Catalog\Block\Product\ImageBuilder
{

    /**
     * Fastly config
     * @var \Fastly\Cdn\Model\Config
     */
    public $config;

    /**
     * @param HelperFactory $helperFactory
     * @param ImageFactory $imageFactory
     * @param \Fastly\Cdn\Model\Config $config
     */
    public function __construct(
        HelperFactory $helperFactory,
        ImageFactory $imageFactory,
        \Fastly\Cdn\Model\Config $config
    ) {
        $this->config = $config;
        parent::__construct($helperFactory, $imageFactory);
    }

    /**
     * Fastly edit - function call for settin up pixel ratios
     * Create image block
     *
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function create()
    {
        /** @var \Magento\Catalog\Helper\Image $helper */
        $helper = $this->helperFactory->create()
            ->init($this->product, $this->imageId);

        $template = $helper->getFrame()
            ? 'Magento_Catalog::product/image.phtml'
            : 'Magento_Catalog::product/image_with_borders.phtml';

        $imagesize = $helper->getResizedImageInfo();

        if ($this->config->isImageOptimizationPixelRatioEnabled()) {
            $this->appendPixelRatios($helper->getUrl());
        }

        $data = [
            'data' => [
                'template' => $template,
                'image_url' => $helper->getUrl(),
                'width' => $helper->getWidth(),
                'height' => $helper->getHeight(),
                'label' => $helper->getLabel(),
                'ratio' =>  $this->getRatio($helper),
                'custom_attributes' => $this->getCustomAttributes(),
                'resized_image_width' => !empty($imagesize[0]) ? $imagesize[0] : $helper->getWidth(),
                'resized_image_height' => !empty($imagesize[1]) ? $imagesize[1] : $helper->getHeight(),
            ],
        ];

        return $this->imageFactory->create($data);
    }

    /**
     * Append Fastly pixel ratios to image
     * @param $url
     */
    public function appendPixelRatios($url)
    {
        $parsedUrl = parse_url($url); // @codingStandardsIgnoreLine - used only for query check
        if (isset($parsedUrl['query'])) {
            $url = $url . '&dpr=1.5 1.5x';
        } else {
            $url = $url . '?dpr=1.5 1.5x';
        }

        $fastlyAttributes = ['srcset' => $url];
        $attributes = $this->getAttributes();
        $this->setAttributes(array_merge($attributes, $fastlyAttributes));
    }

    /**
     * Getter for current attributes
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
