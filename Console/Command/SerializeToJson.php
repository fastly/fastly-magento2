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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SerializeToJson extends Command
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    protected $_cacheManager;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('fastly:format:serializetojson')
            ->setDescription('Converts Module Serialized data to Json format');
    }

    /**
     * SerializeToJson constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Cache\Manager $cacheManager
) {
        parent::__construct();
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->_productMetadata = $productMetadata;
        $this->_cacheManager = $cacheManager;
    }

    /**
     * Converts Fastly serialized data to JSON format
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $configPaths = [
            'geoip_country_mapping' => \Fastly\Cdn\Model\Config::XML_FASTLY_GEOIP_COUNTRY_MAPPING
        ];

        foreach($configPaths as $path){

            $magVer = $this->_productMetadata->getVersion();
            if(version_compare($magVer, '2.2', '<')) {
                echo "Warning : This function is used for converting serialized data to JSON (recommended for Magento versions above 2.2)\n";
            }

            $oldData = $this->_scopeConfig->getValue($path);
            $oldData = @unserialize($oldData);
            if($oldData === false) {
                echo 'Invalid serialization format, unable to unserialize config data : ' . $path . "\n";
                return;
            }
            $oldData = (is_array($oldData)) ? $oldData : array();

            $newData = json_encode($oldData);
            if (false === $newData) {
                throw new \InvalidArgumentException('Unable to encode data.');
            }

            $this->_configWriter->save($path, $newData);
            $this->_cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);

            echo "Config Cache Flushed\n";
        }
    }
}
