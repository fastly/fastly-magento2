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
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Fastly\Cdn\Model\Statistic;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class InstallData
 *
 * @package Fastly\Cdn\Setup
 */
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
