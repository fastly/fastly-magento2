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

use Fastly\Cdn\Model\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateVclCommand extends Command
{
    /**
     * @var Config
     */
    protected $_config;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('fastly:vcl:generate')
            ->setDescription('Generates Fastly VCL and echos it to the command line');
    }

    /**
     * @param Config $config
     */
    public function __construct(Config $config) {
        parent::__construct();
        $this->_config = $config;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $vcl = $this->_config->getVclFile(\Magento\PageCache\Model\Config::VARNISH_4_CONFIGURATION_PATH);
        $output->writeln($vcl);
    }
}
