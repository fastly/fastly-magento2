<?php

namespace Fastly\Cdn\Model\Product;

use Fastly\Cdn\Model\Config;
use Magento\Catalog\Model\Product\Image as ImageModel;
use Magento\PageCache\Model\Config as PageCacheConfig;

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
     * @var bool
     */
    private $isFastlyEnabled = null;

    /**
     * On/Off switch based on config value
     *
     * @return bool
     */
    private function isFastlyImageOptimizationEnabled()
    {
        if ($this->isFastlyEnabled !== null) {
            return $this->isFastlyEnabled;
        }

        $this->isFastlyEnabled = true;

        if ($this->_scopeConfig->isSetFlag(Config::XML_FASTLY_IMAGE_OPTIMIZATIONS) == false) {
            $this->isFastlyEnabled = false;
        }

        if ($this->_scopeConfig->getValue(PageCacheConfig::XML_PAGECACHE_TYPE) !== Config::FASTLY) {
            $this->isFastlyEnabled = false;
        }

        return $this->isFastlyEnabled;
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
        $angle = (int) $angle;

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

        $this->adjustSize();

        return $this;
    }

    private function adjustSize()
    {
        $originalImage = $this->_mediaDirectory->getAbsolutePath($this->getBaseFile());
        $originalSize = getimagesize($originalImage);

        if ($this->getWidth() > $originalSize[0]) {
            $this->setWidth($originalSize[0]);
        }

        if ($this->getHeight() > $originalSize[1]) {
            $this->setHeight($originalSize[1]);
        }

        $this->fastlyParameters['width'] = $this->_width;
        $this->fastlyParameters['height'] = $this->_height;
    }

    /**
     * Return resized product image information
     *
     * @return array
     */
    public function getResizedImageInfo()
    {
        if ($this->isFastlyImageOptimizationEnabled() == false) {
            return parent::getResizedImageInfo();
        }

        // Return image data
        if ($this->getBaseFile() !== null) {
            return [
                0 => $this->getWidth(),
                1 => $this->getHeight()
            ];
        }

        // No image, parse the placeholder
        $asset = $this->_assetRepo->createAsset(
            "Magento_Catalog::images/product/placeholder/{$this->getDestinationSubdir()}.jpg"
        );
        $img = $asset->getSourceFile();
        $imageInfo = getimagesize($img);

        $this->setWidth($imageInfo[0]);
        $this->setHeight($imageInfo[1]);

        return $imageInfo;
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
        $baseFile = $this->getBaseFile();
        if ($baseFile === null) {
            $url = $this->_assetRepo->getUrl(
                "Magento_Catalog::images/product/placeholder/{$this->getDestinationSubdir()}.jpg"
            );
        } else {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            $url .= $baseFile;
        }

        // Add some default parameters
        $this->fastlyParameters['quality'] = $this->_quality;
        $this->fastlyParameters['bg-color'] = implode(',', $this->_backgroundColor);
        if ($this->_keepAspectRatio == true) {
            $this->fastlyParameters['fit'] = 'bounds';
        }

        $url .= '?' . $this->compileFastlyParameters();

        return $url;
    }

    /**
     * Compiles the fastly GET parameters
     *
     * @return string
     */
    private function compileFastlyParameters()
    {
        if (isset($this->fastlyParameters['width']) == false) {
            $this->fastlyParameters['height'] = $this->_height;
            $this->fastlyParameters['width'] = $this->_width;
        }

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
