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
namespace Fastly\Cdn\Console\Command;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Serialize\Serializer\Serialize;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Converts Module Serialized data to Json valid format
 *
 */
class SerializeToJson extends Command
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $productMetadata;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Manager
     */
    private $cacheManager;

    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * @inheritdoc
     */
    protected function configure() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->setName('fastly:format:serializetojson')
            ->setDescription('Converts Module Serialized data to Json format');
    }

    /**
     * SerializeToJson constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param ProductMetadataInterface $productMetadata
     * @param Manager $cacheManager
     * @param Serialize $serialize
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ProductMetadataInterface $productMetadata,
        Manager $cacheManager,
        Serialize $serialize
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->productMetadata = $productMetadata;
        $this->cacheManager = $cacheManager;
        $this->serialize = $serialize;

        parent::__construct();
    }

    /**
     * Converts Fastly serialized data to JSON format
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine - required by parent class
    {

        $configPaths = [
            'geoip_country_mapping' => \Fastly\Cdn\Model\Config::XML_FASTLY_GEOIP_COUNTRY_MAPPING
        ];

        foreach ($configPaths as $path) {
            $magVer = (string)$this->productMetadata->getVersion();
            if (version_compare($magVer, '2.2', '<')) {
                $output->writeln(
                    'Warning : This function is used for converting serialized data to JSON'
                        . ' (recommended for Magento versions above 2.2)'
                );
            }

            $oldData = $this->scopeConfig->getValue($path);

            try {
                $oldData = $this->serialize->unserialize($oldData);
            } catch (\Exception $e) {
                $oldData = false;
            }

            if ($oldData === false) {
                $output->writeln(
                    'Invalid serialization format, unable to unserialize config data : ' . $path
                );

                return Cli::RETURN_FAILURE;
            }
            $oldData = (is_array($oldData)) ? $oldData : [];

            $newData = json_encode($oldData);
            if (false === $newData) {
                $output->writeln('Unable to encode data.');
                return Cli::RETURN_FAILURE;
            }

            $this->configWriter->save($path, $newData); // @codingStandardsIgnoreLine - currently best way to resolve this
            $this->cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);

            $output->writeln('Config Cache Flushed');
            return Cli::RETURN_SUCCESS;
        }
    }
}
