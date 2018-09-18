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
namespace Fastly\Cdn\Setup;

use Fastly\Cdn\Helper\Data;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fastly\Cdn\Model\Statistic;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class UpgradeData
 *
 * @package Fastly\Cdn\Setup
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var DateTime
     */
    private $date;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var Statistic
     */
    private $statistic;
    /**
     * @var Manager
     */
    private $cacheManager;
    /**
     * @var Data
     */
    private $helper;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * UpgradeData constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Statistic $statistic
     * @param DateTime $date
     * @param Manager $cacheManager
     * @param Data $helper
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Statistic $statistic,
        DateTime $date,
        Manager $cacheManager,
        Data $helper,
        ProductMetadataInterface $productMetadata
    ) {
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->statistic = $statistic;
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->cacheManager = $cacheManager;
    }

    /**
     * If old configuration values exist, they're fetched and saved to new config paths.
     * This script will only execute on module version <= 1.0.8
     *
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context*
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $version = $context->getVersion();

        if (!$version) {
            return;
        }

        $oldConfigPaths = [
            'stale_ttl'                 => 'system/full_page_cache/fastly/stale_ttl',
            'stale_error_ttl'           => 'system/full_page_cache/fastly/stale_error_ttl',
            'purge_catalog_category'    => 'system/full_page_cache/fastly/purge_catalog_category',
            'purge_catalog_product'     => 'system/full_page_cache/fastly/purge_catalog_product',
            'purge_cms_page'            => 'system/full_page_cache/fastly/purge_cms_page',
            'soft_purge'                => 'system/full_page_cache/fastly/soft_purge',
            'enable_geoip'              => 'system/full_page_cache/fastly/enable_geoip',
            'geoip_action'              => 'system/full_page_cache/fastly/geoip_action',
            'geoip_country_mapping'     => 'system/full_page_cache/fastly/geoip_country_mapping',
        ];

        $newConfigPaths = [
            'stale_ttl'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/stale_ttl',
            'stale_error_ttl'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/stale_error_ttl',
            'purge_catalog_category'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_catalog_category',
            'purge_catalog_product'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_catalog_product',
            'purge_cms_page'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_cms_page',
            'soft_purge'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/soft_purge',
            'enable_geoip'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/enable_geoip',
            'geoip_action'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/geoip_action',
            'geoip_country_mapping'
                => 'system/full_page_cache/fastly/fastly_advanced_configuration/geoip_country_mapping'
        ];

        $setup->startSetup();

        if (version_compare($version, '1.0.8', '<=')) {
            $this->upgrade108($oldConfigPaths, $newConfigPaths);
        }

        if (version_compare($version, '1.0.9', '<=')) {
            $this->upgrade109($setup);
        }

        // If Magento is upgraded later,
        // use bin/magento fastly:format:serializetojson OR /bin/magento fastly:format:jsontoserialize to adjust format
        $magVer = $this->productMetadata->getVersion();
        if (version_compare($version, '1.0.10', '<=') && version_compare($magVer, '2.2', '>=')) {
            $this->upgrade1010($newConfigPaths);
            $setup->endSetup();
        } elseif (version_compare($magVer, '2.2', '<')) {
            $setup->endSetup();
        }
    }

    /**
     * Config path changes
     * @param $oldConfigPaths
     * @param $newConfigPaths
     */
    private function upgrade108($oldConfigPaths, $newConfigPaths)
    {
        foreach ($oldConfigPaths as $key => $value) {
            $oldValue = $this->scopeConfig->getValue($value);
            if ($oldValue != null) {
                $this->configWriter->save($newConfigPaths[$key], $oldValue); // @codingStandardsIgnoreLine - currently best way to resolve this
            }
        }
    }

    /**
     * GA changes
     * @param ModuleDataSetupInterface $setup
     */
    private function upgrade109(ModuleDataSetupInterface $setup)
    {
        $tableName = $setup->getTable('fastly_statistics');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $data = [
                'action' => Statistic::FASTLY_INSTALLED_FLAG,
                'created_at' => $this->date->date()
            ];

            $setup->getConnection()->insert($tableName, $data); // @codingStandardsIgnoreLine - currently best way to resolve this
        }

        // Save current Fastly module version
        $this->configWriter->save(
            'system/full_page_cache/fastly/current_version',
            $this->helper->getModuleVersion()
        );

        // Generate GA cid and store it for further use
        $this->configWriter->save(
            'system/full_page_cache/fastly/fastly_ga_cid',
            $this->statistic->generateCid()
        );
        $this->cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
        $setup->endSetup();
    }

    /**
     * Convert serialized data to magento supported serialized data
     * @param $newConfigPaths
     */
    private function upgrade1010($newConfigPaths)
    {
        $oldData = $this->scopeConfig->getValue($newConfigPaths['geoip_country_mapping']);
        try {
            $oldData = unserialize($oldData); // @codingStandardsIgnoreLine - used for conversion of old Magento format to json_decode
        } catch (\Exception $e) {
            $oldData = [];
        }
        $oldData = (is_array($oldData)) ? $oldData : [];
        $newData = json_encode($oldData);
        if (false === $newData) {
            throw new \InvalidArgumentException('Unable to encode data.');
        }
        $this->configWriter->save($newConfigPaths['geoip_country_mapping'], $newData);
        $this->cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
    }
}
