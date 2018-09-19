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
namespace Fastly\Cdn\Block\Cache;

use Fastly\Cdn\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Class Additional
 *
 * @package Fastly\Cdn\Block\Cache
 */
class Additional extends Template
{
    const CONTENT_TYPE_HTML    = 'text';
    const CONTENT_TYPE_CSS     = 'css';
    const CONTENT_TYPE_JS      = 'script';
    const CONTENT_TYPE_IMAGE   = 'image';

    /**
     * @var Config
     */
    private $config;

    /**
     * Additional constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;

        parent::__construct($context, $data);
    }

    /**
     * Check if block can be displayed
     *
     * @return bool
     */
    public function canShowBlock()
    {
        if ($this->config->getType() == Config::FASTLY && $this->config->isEnabled()) {
            return true;
        }
        return false;
    }

    /**
     * Get clean by content type url
     *
     * @return string
     */
    public function getCleanByContentTypeUrl()
    {
        return $this->getUrl('*/fastlyCdn_purge/contentType');
    }

    /**
     * Get quick purge url
     *
     * @return string
     */
    public function getQuickPurgeUrl()
    {
        return $this->getUrl('*/fastlyCdn_purge/quick');
    }

    /**
     * Get purge all url
     *
     * @return string
     */
    public function getPurgeAllUrl()
    {
        return $this->getUrl('*/fastlyCdn_purge/all');
    }

    /**
     * Get clean by source url
     *
     * @return string
     */
    public function getCleanByStoreUrl()
    {
        return $this->getUrl('*/fastlyCdn_purge/store');
    }

    /**
     * Get store options
     *
     * @return array
     */
    public function getStoreOptions()
    {
        return $this->_storeManager->getStores();
    }

    /**
     * Get content types as option array
     *
     * @return array
     */
    public function getContentTypeOptions()
    {
        $contentTypes = [
            self::CONTENT_TYPE_HTML  => __('HTML'),
            self::CONTENT_TYPE_CSS   => __('CSS'),
            self::CONTENT_TYPE_JS    => __('JavaScript'),
            self::CONTENT_TYPE_IMAGE => __('Images')
        ];
        return $contentTypes;
    }
}
