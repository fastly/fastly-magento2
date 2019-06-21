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
namespace Fastly\Cdn\Model\View\Asset;

use Fastly\Cdn\Model\Config;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Media\ConfigInterface;
use Magento\Catalog\Model\View\Asset\Image as ImageModel;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\View\Asset\ContextInterface;
use Magento\PageCache\Model\Config as PageCacheConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Image
 * @package Fastly\Cdn\Model\View\Asset
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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ImageHelper
     */
    private $imageHelper;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * Misc image params depend on size, transparency, quality, watermark etc.
     *
     * @var array
     */
    private $miscParams;

    /**
     * Image constructor.
     * @param ConfigInterface $mediaConfig
     * @param ContextInterface $context
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param ImageHelper $imageHelper
     * @param StoreManagerInterface $storeManager
     * @param string $filePath
     * @param array $miscParams
     */
    public function __construct(
        ConfigInterface $mediaConfig,
        ContextInterface $context,
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        ImageHelper $imageHelper,
        StoreManagerInterface $storeManager,
        $filePath,
        array $miscParams
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->imageHelper = $imageHelper;
        $this->storeManager = $storeManager;
        $this->miscParams = $miscParams;
        parent::__construct($mediaConfig, $context, $encryptor, $filePath, $miscParams);
    }

    /**
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getUrl()
    {
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

        if ($this->scopeConfig->isSetFlag(Config::XML_FASTLY_IMAGE_OPTIMIZATIONS) == false) {
            $this->isFastlyEnabled = false;
        }

        if ($this->scopeConfig->getValue(PageCacheConfig::XML_PAGECACHE_TYPE) !== Config::FASTLY) {
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

        if (empty($this->scopeConfig->isSetFlag(Config::XML_FASTLY_FORCE_LOSSY))) {
            $this->isForceLossyEnabled = false;
        }

        if ($this->scopeConfig->getValue(PageCacheConfig::XML_PAGECACHE_TYPE) !== Config::FASTLY) {
            $this->isForceLossyEnabled = false;
        }

        return $this->isForceLossyEnabled;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getForceLossyUrl()
    {
        $baseFile = $this->getSourceFile();
        $extension = pathinfo($baseFile, PATHINFO_EXTENSION); // @codingStandardsIgnoreLine
        $url = $this->getBaseFileUrl($baseFile);
        if ($extension == 'png') {
            if ($this->isFastlyImageOptimizationEnabled() == false) {
                $this->lossyUrl = $url . '?format=jpeg';
            } else {
                $this->lossyParam = '&format=jpeg';
            }
        }
    }

    /**
     * @param $baseFile
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseFileUrl($baseFile)
    {
        if ($baseFile === null) {
            $url = $this->imageHelper->getDefaultPlaceholderUrl();
        } else {
            $url = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $url .= $baseFile;
        }

        return $url;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function getFastlyUrl()
    {
        $baseFile = $this->getSourceFile();
        $url = $this->getBaseFileUrl($baseFile);

        $imageQuality = $this->scopeConfig->getValue(Config::XML_FASTLY_IMAGE_OPTIMIZATION_IMAGE_QUALITY);

        $this->fastlyParameters['quality'] = $imageQuality;

        if ($this->scopeConfig->isSetFlag(Config::XML_FASTLY_IMAGE_OPTIMIZATION_BG_COLOR) == true) {
            $this->fastlyParameters['bg-color'] = implode(',', $this->miscParams['background']);
        }
        if ($this->miscParams['keep_aspect_ratio'] == true) {
            $this->fastlyParameters['fit'] = 'bounds';
        }
        if (isset($this->miscParams['angle'])) {
            $this->fastlyRotate($this->miscParams['angle']);
        }
        $this->fastlyUrl = $url . '?' . $this->compileFastlyParameters();
    }

    /**
     * Compiles the fastly GET parameters
     *
     * @return string
     */
    private function compileFastlyParameters()
    {
        if (isset($this->fastlyParameters['width']) == false) {
            $this->fastlyParameters['height'] = $this->miscParams['image_height'];
            $this->fastlyParameters['width'] = $this->miscParams['image_width'];
        }

        if ($this->scopeConfig->isSetFlag(Config::XML_FASTLY_IMAGE_OPTIMIZATION_CANVAS) == true) {
            // Make sure Fastly delivers the specified size, even with letterboxing or pillarboxing.
            // We'll use aspect ratio canvas to avoid issues when using dpr
            $canvas = "{$this->miscParams['image_width']}:{$this->miscParams['image_height']}";
            $this->fastlyParameters['canvas'] = $canvas;
        }

        $params = [];
        foreach ($this->fastlyParameters as $key => $value) {
            $params[] = $key . '=' . $value;
        }

        return implode('&', $params);
    }

    /**
     * @param $angle
     * @return $this
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
}
