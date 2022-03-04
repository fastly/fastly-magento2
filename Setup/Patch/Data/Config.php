<?php

declare(strict_types=1);

namespace Fastly\Cdn\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Fastly\Cdn\Helper\Data;
use Fastly\Cdn\Model\Statistic;
use Magento\Framework\Serialize\Serializer\Serialize;

class Config implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Statistic
     */
    private $statistic;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * Config constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $configWriter
     * @param Data $helper
     * @param Statistic $statistic
     * @param ScopeConfigInterface $scopeConfig
     * @param Serialize $serialize
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $configWriter,
        Data $helper,
        Statistic $statistic,
        ScopeConfigInterface $scopeConfig,
        Serialize $serialize
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->configWriter = $configWriter;
        $this->helper = $helper;
        $this->statistic = $statistic;
        $this->scopeConfig = $scopeConfig;
        $this->serialize = $serialize;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        if (!$this->checkIsExistConfigValue('system/full_page_cache/fastly/current_version')) {
            $this->configWriter
                ->save(
                    'system/full_page_cache/fastly/current_version',
                    $this->helper->getModuleVersion()
                );
        }

        if (!$this->checkIsExistConfigValue('system/full_page_cache/fastly/fastly_ga_cid')) {
            $this->configWriter->save('system/full_page_cache/fastly/fastly_ga_cid', $this->statistic->generateCid());
        }

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

        $this->upgrade108($oldConfigPaths, $newConfigPaths);
        $this->fixGeoIpMapping($newConfigPaths);
        return $this;
    }

    /**
     * Fix GeoIP Mapping convert unserialize to json
     *
     * @param array $newConfigPaths
     */
    private function fixGeoIpMapping(array $newConfigPaths): void
    {
        $oldData = $this->scopeConfig->getValue($newConfigPaths['geoip_country_mapping']);
        if (!$oldData) {
            return;
        }

        try {
            $oldData = $this->serialize->unserialize($oldData);
        } catch (\Exception $e) {
            return;
        }

        if (!is_array($oldData)) {
            return;
        }

        $newData = \json_encode($oldData);
        $this->configWriter->save($newConfigPaths['geoip_country_mapping'], $newData);
    }

    /**
     * Fix old config paths
     *
     * @param array $oldConfigPaths
     * @param array $newConfigPaths
     */
    private function upgrade108(array $oldConfigPaths, array $newConfigPaths): void
    {
        foreach ($oldConfigPaths as $key => $value) {
            $oldValue = $this->scopeConfig->getValue($value);
            if ($oldValue != null) {
                $this->configWriter->save($newConfigPaths[$key], $oldValue); // @codingStandardsIgnoreLine - currently best way to resolve this
            }
        }
    }

    /**
     * Check config Value
     *
     * @param string $configPath
     * @param string $scope
     * @param int $scopeId
     * @return bool
     */
    private function checkIsExistConfigValue(
        string $configPath,
        string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        int $scopeId = 0
    ): bool {
        $tableName = $this->moduleDataSetup->getTable('core_config_data');
        $select = $this->moduleDataSetup->getConnection()->select()->from(
            $tableName,
            ['value']
        )->where(
            'path = ?',
            $configPath
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_id = ?',
            $scopeId
        );
        $value = $this->moduleDataSetup->getConnection()->fetchOne($select);
        return !($value === false);
    }
}
