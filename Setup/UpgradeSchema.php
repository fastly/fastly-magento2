<?php

namespace Fastly\Cdn\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

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

        /** @var AdapterInterface $connection */
        $connection = $installer->getConnection();

        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.9', '<=')) {
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
                $installer->endSetup();
            }
        }
    }
}
