<?php

declare(strict_types=1);

namespace Fastly\Cdn\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Fastly\Cdn\Model\Statistic;

class InstalledFlag implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * InstalledFlag constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param DateTime $date
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        DateTime $date
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->date = $date;
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
        $tableName = $this->moduleDataSetup->getTable('fastly_statistics');
        if ($this->moduleDataSetup->tableExists($tableName)) {
            $select = $this->moduleDataSetup->getConnection()->select()->from(
                $tableName,
                ['action']
            )->where(
                'action = ?',
                Statistic::FASTLY_INSTALLED_FLAG
            );
            $value = $this->moduleDataSetup->getConnection()->fetchOne($select);
            if (!$value) {
                $data = [
                    'action' => Statistic::FASTLY_INSTALLED_FLAG,
                    'created_at' => $this->date->date()
                ];
                $this->moduleDataSetup->getConnection()->insert($tableName, $data);
            }
        }
        return $this;
    }
}
