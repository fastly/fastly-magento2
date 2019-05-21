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

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 *
 * @package Fastly\Cdn\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface // @codingStandardsIgnoreLine - currently best way to resolve this
{

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.11', '<=')) {
            $this->createFastlyStatisticsTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.12', '<=')) {
            $this->createModlyManifestTable($installer);
        }

        if (version_compare($context->getVersion(), '1.0.13', '<=')) {
            $this->upgradeModlyManifestTable($installer);
        }

        $installer->endSetup();
    }

    /**
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    public function createFastlyStatisticsTable(
        SchemaSetupInterface $installer
    ) {
        $connection = $installer->getConnection();
        $tableName = $installer->getTable('fastly_statistics');

        if ($installer->getConnection()->isTableExists($tableName) != true) {
            /**
             * Create table 'fastly_statistics'
             */
            $table = $connection->newTable(
                $installer->getTable('fastly_statistics')
            )->addColumn(
                'stat_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Stat id'
            )->addColumn(
                'action',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                30,
                ['nullable' => false],
                'Fastly action'
            )->addColumn(
                'sent',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => 0],
                '1 = Curl req. sent | 0 = Curl req. not sent'
            )->addColumn(
                'state',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'default' => 0],
                '1 = configured | 0 = not_configured'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                [],
                'Action date'
            );

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $installer
     * @throws \Zend_Db_Exception
     */
    public function createModlyManifestTable(
        SchemaSetupInterface $installer
    ) {
        $connection = $installer->getConnection();
        $tableName = $installer->getTable('fastly_modly_manifests');

        if ($installer->getConnection()->isTableExists($tableName) != true) {
            /**
             * Create table 'fastly_modly_manifests'
             */
            $table = $connection->newTable(
                $installer->getTable('fastly_modly_manifests')
            )->addColumn(
                'manifest_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['identity' => false, 'nullable' => false, 'primary' => true],
                'Manifest id'
            )->addColumn(
                'manifest_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                64,
                ['nullable' => false],
                'Manifest name'
            )->addColumn(
                'manifest_description',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Manifest description'
            )->addColumn(
                'manifest_version',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                12,
                ['nullable' => false],
                'Manifest version'
            )->addColumn(
                'manifest_properties',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Manifest properties'
            )->addColumn(
                'manifest_content',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Manifest content'
            )->addColumn(
                'manifest_vcl',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Manifest VCL'
            )->addColumn(
                'manifest_values',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                \Magento\Framework\DB\Ddl\Table::DEFAULT_TEXT_SIZE,
                ['nullable' => false],
                'Manifest configuration values'
            )->addColumn(
                'manifest_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                1,
                ['nullable' => false],
                'Manifest status'
            );

            $connection->createTable($table);
        }
    }

    /**
     * @param SchemaSetupInterface $installer
     */
    public function upgradeModlyManifestTable(
        SchemaSetupInterface $installer
    ) {
        $connection = $installer->getConnection();
        $tableName = $installer->getTable('fastly_modly_manifests');

        if ($installer->getConnection()->isTableExists($tableName) == true) {
            $connection->addColumn(
                $tableName,
                'last_uploaded',
                [
                    'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                    'nullable'  => true,
                    'comment'   => 'Last uploaded',
                ]
            );
        }
    }
}
