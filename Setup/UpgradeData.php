<?php

namespace Fastly\Cdn\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    /**
     * UpgradeSchema constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
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

        $oldConfigPaths = [
            'stale_ttl' => 'system/full_page_cache/fastly/stale_ttl',
            'stale_error_ttl' => 'system/full_page_cache/fastly/stale_error_ttl',
            'purge_catalog_category' => 'system/full_page_cache/fastly/purge_catalog_category',
            'purge_catalog_product' => 'system/full_page_cache/fastly/purge_catalog_product',
            'purge_cms_page' => 'system/full_page_cache/fastly/purge_cms_page',
            'soft_purge' => 'system/full_page_cache/fastly/soft_purge',
            'enable_geoip' => 'system/full_page_cache/fastly/enable_geoip',
            'geoip_action' => 'system/full_page_cache/fastly/geoip_action',
            'geoip_country_mapping' => 'system/full_page_cache/fastly/geoip_country_mapping',
        ];

        $newConfigPaths = [
            'stale_ttl' => 'system/full_page_cache/fastly/fastly_advanced_configuration/stale_ttl',
            'stale_error_ttl' => 'system/full_page_cache/fastly/fastly_advanced_configuration/stale_error_ttl',
            'purge_catalog_category' => 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_catalog_category',
            'purge_catalog_product' => 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_catalog_product',
            'purge_cms_page' => 'system/full_page_cache/fastly/fastly_advanced_configuration/purge_cms_page',
            'soft_purge' => 'system/full_page_cache/fastly/fastly_advanced_configuration/soft_purge',
            'enable_geoip' => 'system/full_page_cache/fastly/fastly_advanced_configuration/enable_geoip',
            'geoip_action' => 'system/full_page_cache/fastly/fastly_advanced_configuration/geoip_action',
            'geoip_country_mapping' => 'system/full_page_cache/fastly/fastly_advanced_configuration/geoip_country_mapping'
        ];

        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.8', '<=')) {
            foreach ($oldConfigPaths as $key => $value) {
                $oldValue = $this->_scopeConfig->getValue($value);
                if($oldValue != null)
                {
                    $this->_configWriter->save($newConfigPaths[$key], $oldValue);
                }
            }
        }

        $setup->endSetup();
    }
}