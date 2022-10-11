<?php
declare(strict_types=1);

namespace Fastly\Cdn\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Fastly\Cdn\Model\Config as FastlyConfig;
use Magento\Setup\Exception;

/**
 * Class RateLimitPath for adding rate limit placeholder
 */
class RateLimitPath implements DataPatchInterface
{

    private const CONFIG_PLACE_HOLDER = '[{"path":"\^/fastly-io-tester$","comment":"Default Fastly Placeholder"}]';

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * RateLimitPath constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
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
     * @inheritDoc
     */
    public function apply(): self
    {
        $tableName = $this->moduleDataSetup->getTable('core_config_data');
        $select = $this->moduleDataSetup->getConnection()->select()->from(
            $tableName,
            ['value']
        )->where(
            'path = ?',
            FastlyConfig::XML_FASTLY_RATE_LIMITING_PATHS
        )->where(
            'scope = ?',
            'default'
        )->where(
            'scope_id = ?',
            '0'
        );
        $value = (string)$this->moduleDataSetup->getConnection()->fetchOne($select);
        try {
            $paths = (array)\json_decode($value);
            if (empty($paths)) {
                $data = [
                    'path' => FastlyConfig::XML_FASTLY_RATE_LIMITING_PATHS,
                    'scope' => 'default',
                    'scope_id' => '0',
                    'value' => self::CONFIG_PLACE_HOLDER
                ];
                $this->moduleDataSetup->getConnection()->insertOnDuplicate($tableName, $data, ['value']);
            }

        } catch (\Exception $e) {//phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        }
        return $this;
    }
}
