<?php

namespace Fastly\Cdn\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fastly\Cdn\Model\Statistic;

class InstallData implements InstallDataInterface
{
    /**
     * Date model
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    /**
     * @var Statistic
     */
    protected $_statistic;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    protected $_cacheManager;

    /**
     * @var \Fastly\Cdn\Helper\Data
     */
    protected $_helper;

    /**
     * InstallData constructor
     *
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param Statistic $statistic
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     * @param \Fastly\Cdn\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        Statistic $statistic,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Fastly\Cdn\Helper\Data $helper
    )
    {
        $this->_date = $date;
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->_statistic = $statistic;
        $this->_cacheManager = $cacheManager;
        $this->_helper = $helper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $tableName = $setup->getTable('fastly_statistics');
        if($setup->getConnection()->isTableExists($tableName) == true) {

            $data = [
                'action' => Statistic::FASTLY_INSTALLED_FLAG,
                'created_at' => $this->_date->date()
            ];

            $setup->getConnection()->insert($tableName, $data);
        }

        // Save current Fastly module version
        $this->_configWriter->save('system/full_page_cache/fastly/current_version', $this->_helper->getModuleVersion());

        // Generate GA cid and store it for further use
        $this->_configWriter->save('system/full_page_cache/fastly/fastly_ga_cid', $this->_statistic->generateCid());
        $this->_cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
        $setup->endSetup();
    }
}