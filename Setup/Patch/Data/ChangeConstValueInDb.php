<?php

declare(strict_types=1);

namespace Fastly\Cdn\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeConstValueInDb implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * ChangeConstValueInDb constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
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
     * @inheritdoc
     */
    public function apply(): void
    {
        $this->changeConstValueInDb($this->moduleDataSetup);
    }

    /**
     * Change old const value from 'fastly' to '42'
     *
     * @param ModuleDataSetupInterface $setup
     */
    private function changeConstValueInDb(ModuleDataSetupInterface $setup): void
    {
        $select = $setup->getConnection()->select()->from(
            $setup->getTable('core_config_data'),
            ['value']
        )->where(
            'path = ?',
            \Magento\PageCache\Model\Config::XML_PAGECACHE_TYPE
        );
        $value = $setup->getConnection()->fetchOne($select);
        if ($value == 'fastly') {
            $row = [
                'value' => \Fastly\Cdn\Model\Config::FASTLY
            ];
            $setup->getConnection()->update(
                $setup->getTable('core_config_data'),
                $row,
                ['path = ?' => \Magento\PageCache\Model\Config::XML_PAGECACHE_TYPE]
            );
        }
    }
}
