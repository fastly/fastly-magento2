<?php

namespace Fastly\Cdn\Model\Product;

use Magento\Catalog\Model\Product\Image as ImageModel;

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
class Image extends ImageModel
{
    /**
     * @var array
     */
    private $fastlyParameters = [];

    /**
     * @var array
     */
    private $fastlyOverlay = [];

    /**
     * On/Off switch based on conffig value
     *
     * @return bool
     */
    private function isFastlyImageOptimizationEnabled()
    {
        return $this->_scopeConfig->isSetFlag(
            \Fastly\Cdn\Model\Config::XML_FASTLY_IMAGE_OPTIMIZATIONS
        );
    }

    /**
     * Wrapper for original rotate()
     *
     * @param int $angle
     * @return \Magento\Catalog\Model\Product\Image
     */
    public function rotate($angle)
    {
        if ($this->isFastlyImageOptimizationEnabled() == false) {
            return parent::rotate($angle);
        }

        return $this->fastlyRotate($angle);
    }

    /**
     * Fastly implementation of rotation
     *
     * @param int $angle
     * @return \Magento\Catalog\Model\Product\Image
     */
    private function fastlyRotate($angle)
    {
        $angle = intval($angle);

        $orient = null;
        if ($angle == 90) {
            $orient = 'r';
        }

        if ($angle == -90 || $angle == 270) {
            $orient = 'l';
        }

        if ($angle == 180) {
            $orient = 3;
        }

        if ($orient !== null) {
            $this->fastlyParameters['orient'] = $orient;
        }

        return $this;
    }

    /**
     * Wrapper for original resize()
     *
     * @see \Magento\Framework\Image\Adapter\AbstractAdapter
     * @return \Magento\Catalog\Model\Product\Image
     */
    public function resize()
    {
        if ($this->isFastlyImageOptimizationEnabled() == false) {
            return parent::resize();
        }

        return $this->fastlyResize();
    }

    /**
     * Fastly implementation of resize
     *
     * @see \Magento\Framework\Image\Adapter\AbstractAdapter
     * @return \Magento\Catalog\Model\Product\Image
     */
    private function fastlyResize()
    {
        if ($this->getWidth() === null && $this->getHeight() === null) {
            return $this;
        }
        $this->fastlyParameters['width'] = $this->_width;
        $this->fastlyParameters['height'] = $this->_height;

        return $this;
    }

    /**
     * Add watermark to image
     * size param in format 100x200
     *
     * @param string $file
     * @param string $position
     * @param array $size ['width' => int, 'height' => int]
     * @param int $width
     * @param int $height
     * @param int $opacity
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function setWatermark(
        $file,
        $position = null,
        $size = null,
        $width = null,
        $height = null,
        $opacity = null
    ) {
        if ($this->_isBaseFilePlaceholder) {
            return $this;
        }

        if ($file) {
            $this->setWatermarkFile($file);
        } else {
            return $this;
        }

        if ($position) {
            $this->setWatermarkPosition($position);
        }
        if ($size) {
            $this->setWatermarkSize($size);
        }
        if ($width) {
            $this->setWatermarkWidth($width);
        }
        if ($height) {
            $this->setWatermarkHeight($height);
        }
        if ($opacity) {
            $this->setWatermarkImageOpacity($opacity);
        }
        $filePath = $this->_getWatermarkFilePath();

        if ($filePath) {
            $this->fastlyOverlay['overlay'] = $filePath;
            $this->fastlyOverlay['overlay-width'] = $this->getWatermarkWidth();
            $this->fastlyOverlay['overlay-height'] = $this->getWatermarkHeight();

            $this->fastlyOverlay['overlay-align'] = $this->fastlyMapPosition($this->getWatermarkPosition());
            if ($this->getWatermarkPosition() == 'tile') {
                $this->fastlyOverlay['overlay-repeat'] = 'both';
            }
        }

        return $this;
    }

    /**
     * Transaltes Magento position to Fastly position
     *
     * @param string $position
     * @return string
     */
    private function fastlyMapPosition($position)
    {
        $map = [
            'stretch'       => 'middle, center',
            'tile'          => 'top,left',
            'top-left'      => 'top,left',
            'top-right'     => 'top,right',
            'bottom-left'   => 'bottom,left',
            'bottom-right'  => 'bottom,right',
            'center'        => 'middle,center'
        ];

        if (array_key_exists($position, $map) === true) {
            $position = 'center';
        }

        return $map[$position];
    }

    /**
     * Wrapper for original saveFile()
     *
     * @return \Magento\Catalog\Model\Product\Image
     */
    public function saveFile()
    {
        if ($this->isFastlyImageOptimizationEnabled() == false) {
            return parent::saveFile();
        }

        return $this;
    }

    /**
     * Wrapper for original getUrl()
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->isFastlyImageOptimizationEnabled() == false) {
            return parent::getUrl();
        }

        return $this->getFastlyUrl();
    }

    /**
     * Builds URL used for fastly service
     *
     * @return string
     */
    public function getFastlyUrl()
    {
        $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
        $url .= $this->getBaseFile();

        // Add some default parameters
        $this->fastlyParameters['quality'] = $this->_quality;
        $this->fastlyParameters['bg-color'] = implode(',', $this->_backgroundColor);
        if ($this->_keepAspectRatio == true) {
            $this->fastlyParameters['fit'] = 'bounds';
        }

        $url .= '?' . $this->compileFastlyParameters();

        // TODO: Watermark not implemented

        return $url;
    }

    /**
     * Compiles the fastly GET parameters
     *
     * @return string
     */
    private function compileFastlyParameters()
    {
        $params = [];
        foreach ($this->fastlyParameters as $key => $value) {
            $params[] = $key . '=' . $value;
        }

        return implode('&', $params);
    }

    /**
     * Wrapper for original isCached()
     *
     * @return bool
     */
    public function isCached()
    {
        if ($this->isFastlyImageOptimizationEnabled() == false) {
            return parent::isCached();
        }

        return false;
    }
}
