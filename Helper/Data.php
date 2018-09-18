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
namespace Fastly\Cdn\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * @package Fastly\Cdn\Helper
 */
class Data extends AbstractHelper
{
    const FASTLY_MODULE_NAME = 'Fastly_Cdn';
    /**
     * @var ModuleListInterface
     */
    private $moduleList;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleList = $moduleList;
        $this->storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * Return Fastly module version
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::FASTLY_MODULE_NAME)['setup_version'];
    }

    /**
     * Return Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }

    /**
     * Return Store URL
     *
     * @return mixed
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }
}
