<?php

namespace Fastly\Cdn\Setup;

use Fastly\Cdn\Helper\Data;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fastly\Cdn\Model\Statistic;
use Magento\Framework\Stdlib\DateTime\DateTime;

class InstallData implements InstallDataInterface
{
    /**
     * @var DateTime
     */
    private $date;

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
     * InstallData constructor
     *
     * @param DateTime $date
     * @param WriterInterface $configWriter
     * @param Statistic $statistic
     * @param Manager $cacheManager
     * @param Data $helper
     */
    public function __construct(
        DateTime $date,
        WriterInterface $configWriter,
        Statistic $statistic,
        Manager $cacheManager,
        Data $helper
    ) {
        $this->date = $date;
        $this->configWriter = $configWriter;
        $this->statistic = $statistic;
        $this->cacheManager = $cacheManager;
        $this->helper = $helper;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) // @codingStandardsIgnoreLine - unused parameter
    {
        $setup->startSetup();
        $tableName = $setup->getTable('fastly_statistics');
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            $data = [
                'action' => Statistic::FASTLY_INSTALLED_FLAG,
                'created_at' => $this->date->date()
            ];

            $setup->getConnection()->insert($tableName, $data); // @codingStandardsIgnoreLine - currently best way to resolve this
        }

        // Save current Fastly module version
        $this->configWriter->save('system/full_page_cache/fastly/current_version', $this->helper->getModuleVersion());

        // Generate GA cid and store it for further use
        $this->configWriter->save('system/full_page_cache/fastly/fastly_ga_cid', $this->statistic->generateCid());
        $this->cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
        $setup->endSetup();
    }
}
