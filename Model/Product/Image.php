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
namespace Fastly\Cdn\Model\Product;

use Fastly\Cdn\Model\Config;
use Magento\Catalog\Model\Product\Image as ImageModel;
use Magento\PageCache\Model\Config as PageCacheConfig;
use Magento\Catalog\Helper\Image as ImageHelper;

/**
 * Class Image
 *
 * @package Fastly\Cdn\Model\Product
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
     * @var null
     */
    private $isForceLossyEnabled = null;
    /**
     * @var null
     */
    private $lossyParam = null;
    /**
     * @var null
     */
    private $lossyUrl = null;
    /**
     * @var null
     */
    private $fastlyUrl = null;

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
     * @return bool|null
     */
    public function isForceLossyEnabled()
    {
        if ($this->isForceLossyEnabled !== null) {
            return $this->isForceLossyEnabled;
        }

        $this->isForceLossyEnabled = true;

        if (empty($this->_scopeConfig->isSetFlag(Config::XML_FASTLY_FORCE_LOSSY))) {
            $this->isForceLossyEnabled = false;
        }

        if ($this->_scopeConfig->getValue(PageCacheConfig::XML_PAGECACHE_TYPE) !== Config::FASTLY) {
            $this->isForceLossyEnabled = false;
        }

        return $this->isForceLossyEnabled;
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

        if ($this->_scopeConfig->isSetFlag(Config::XML_FASTLY_IMAGE_OPTIMIZATION_CANVAS) == true) {
            // Make sure Fastly delivers the specified size, even with letterboxing or pillarboxing.
            // We'll use aspect ratio canvas to avoid issues when using dpr
            $this->fastlyParameters['canvas'] = "{$this->_width}:{$this->_height}";
        }
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
        // returns original url if IO and force lossy are disabled
        if ($this->isFastlyImageOptimizationEnabled() == false && $this->isForceLossyEnabled() == false) {
            return parent::getUrl();
        }
        // retrieves force lossy url or param if force lossy is enabled
        if ($this->isForceLossyEnabled() != false) {
            $this->getForceLossyUrl();
        }
        // retrieves Fastly url if force lossy url is not set
        if (!$this->lossyUrl) {
            $this->getFastlyUrl();
        }
        // returns url with set parameters
        if ($this->lossyParam) {
            return $this->fastlyUrl . $this->lossyParam;
        } elseif ($this->lossyUrl) {
            return $this->lossyUrl;
        } else {
            return $this->fastlyUrl;
        }
    }

    /**
     * Creates a force lossy url param or url + param depending if IO is disabled or enabled
     */
    public function getForceLossyUrl()
    {
        $baseFile = $this->getBaseFile();
        $extension = pathinfo($baseFile, PATHINFO_EXTENSION); // @codingStandardsIgnoreLine
        $url = $this->getBaseFileUrl($baseFile);
        if ($extension == 'png' || $extension == 'bmp') {
            if ($this->isFastlyImageOptimizationEnabled() == false) {
                $this->lossyUrl = $url . '?format=jpeg';
            } else {
                $this->lossyParam = '&format=jpeg';
            }
        }
    }

    /**
     * Creates a url with fastly parameters
     */
    public function getFastlyUrl()
    {
        $baseFile = $this->getBaseFile();
        $url = $this->getBaseFileUrl($baseFile);

        $imageQuality = $this->_scopeConfig->getValue(Config::XML_FASTLY_IMAGE_OPTIMIZATION_IMAGE_QUALITY);

        $this->setQuality($imageQuality);

        $this->fastlyParameters['quality'] = $this->_quality;

        if ($this->_scopeConfig->isSetFlag(Config::XML_FASTLY_IMAGE_OPTIMIZATION_BG_COLOR) == true) {
            $this->fastlyParameters['bg-color'] = implode(',', $this->_backgroundColor);
        }
        if ($this->_keepAspectRatio == true) {
            $this->fastlyParameters['fit'] = 'bounds';
        }
        $this->fastlyUrl = $url . '?' . $this->compileFastlyParameters();
    }

    public function getBaseFileUrl($baseFile)
    {
        if ($baseFile === null || $this->isBaseFilePlaceholder()) {
            $imageHelper = \Magento\Framework\App\ObjectManager::getInstance()->get(ImageHelper::class);
            $url = $imageHelper->getDefaultPlaceholderUrl($this->getDestinationSubdir());
        } else {
            $url = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            $url .= $baseFile;
        }

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
