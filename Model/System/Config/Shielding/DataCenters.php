<?php

namespace Fastly\Cdn\Model\System\Config\Shielding;

use Fastly\Cdn\Model\Config;
use InvalidArgumentException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class DataCenters
 * @package Fastly\Cdn\Model\System\Config\Shielding
 */
class DataCenters
{
    /**
     * @var File
     */
    private $driverFile;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * DataCenters constructor.
     * @param File $driverFile
     * @param SerializerInterface $serializer
     * @param Reader $reader
     */
    public function __construct(
        File $driverFile,
        SerializerInterface $serializer,
        Reader $reader
    ) {
        $this->driverFile = $driverFile;
        $this->reader = $reader;
        $this->serializer = $serializer;
    }

    /**
     * @return array
     */
    public function getShieldingPoints(): array
    {
       $etcPath = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, Config::FASTLY_MODULE_NAME);
       $shieldingPath = $etcPath . Config::SHIELDING_PATH . Config::DATACENTER_FILE;
       try {
           if (!$this->driverFile->isExists($shieldingPath))
               return [];

           if (!$dataCenters = $this->driverFile->fileGetContents($shieldingPath))
               return [];

           if (!$dataCenters = $this->serializer->unserialize($dataCenters))
               return [];

           return $this->groupDataCenters($dataCenters);

       } catch (InvalidArgumentException | FileSystemException $e) {
           return [];
       }
    }

    /**
     * @param array $dataCenters
     * @return array
     */
    protected function groupDataCenters(array $dataCenters): array
    {
        if (!$dataCenters)
            return [];

        $data = [];
        foreach ($dataCenters as $dataCenter) {
            if (!isset($dataCenter->group, $dataCenter->name, $dataCenter->code, $dataCenter->shield))
                continue;

            $data[$dataCenter->group][] = [
                'value'    => $dataCenter->shield,
                'label'     => $dataCenter->name . ' (' . $dataCenter->code . ')'
            ];
        }

        return $data;
    }
}
