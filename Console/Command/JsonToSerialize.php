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
use Magento\Framework\Serialize\SerializerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class JsonToSerialize
 *
 * @package Fastly\Cdn\Console\Command
 */
class JsonToSerialize extends Command
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * JsonToSerialize constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param ProductMetadataInterface $productMetadata
     * @param SerializerInterface $serializer
     * @param Manager $cacheManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        ProductMetadataInterface $productMetadata,
        SerializerInterface $serializer,
        Manager $cacheManager
    ) {
        parent::__construct();

        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->productMetadata = $productMetadata;
        $this->cacheManager = $cacheManager;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    protected function configure() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->setName('fastly:format:jsontoserialize')
            ->setDescription('Converts Module JSON data to serialized format');
    }

    /**
     * Converts Fastly JSON data to serialized format
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
            $magVer = $this->productMetadata->getVersion();

            if (version_compare($magVer, '2.2', '>=')) {
                $output->writeln('Warning : This function is used for converting JSON data to serialized format'
                . '(used only to revert changes made by : bin/magento fastly:format:serializetojson)');
            }

            $oldData = $this->scopeConfig->getValue($path);
            $oldData = json_decode($oldData, true);

            if ($oldData === false || $oldData === null) {
                $output->writeln('Invalid JSON format, unable to decode config data : ' . $path);
                return;
            }

            $oldData = (is_array($oldData)) ? $oldData : [];
            $newData = $this->serializer->serialize($oldData);

            if (false === $newData) {
                throw new \InvalidArgumentException('Unable to serialize data.');
            }

            $this->configWriter->save($path, $newData); // @codingStandardsIgnoreLine - currently best way to resolve this
            $this->cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);

            $output->writeln('Config Cache Flushed');
        }
    }
}
