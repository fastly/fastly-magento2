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
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ConfigGetCommand
 *
 * @package Fastly\Cdn\Console\Command
 */
class ConfigGetCommand extends Command
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @inheritdoc
     */
    protected function configure() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->setName('fastly:conf:get')
            ->setDescription('Enables Fastly as Full Page Cache Caching Application');

        $this->addOption(
            'fastly-status',
            'e',
            InputOption::VALUE_NONE,
            'Get Fastly status.'
        );

        $this->addOption(
            'service-id',
            's',
            InputOption::VALUE_NONE,
            'Get Fastly Service ID.'
        );

        $this->addOption(
            'token',
            't',
            InputOption::VALUE_NONE,
            'Get Fastly Token.'
        );

        $this->addOption(
            'admin-path-timeout',
            'A',
            InputOption::VALUE_NONE,
            'Get Fastly Admin Path Timeout.'
        );

        $this->addOption(
            'stale-content-delivery-time',
            'S',
            InputOption::VALUE_NONE,
            'Get time in seconds that Fastly will serve stale content while fresh content is being requested.'
        );

        $this->addOption(
            'stale-content-delivery-time-error',
            'B',
            InputOption::VALUE_NONE,
            'Get time in seconds that Fastly will continue to serve stale content if your origin is unavailable.'
        );

        $this->addOption(
            'ignored-url-parameters',
            'I',
            InputOption::VALUE_NONE,
            'Get a comma separated list of ignored query string parameters.'
        );

        $this->addOption(
            'purge-category',
            'C',
            InputOption::VALUE_NONE,
            'Get purge category status.'
        );

        $this->addOption(
            'purge-product',
            'P',
            InputOption::VALUE_NONE,
            'Get purge product status.'
        );

        $this->addOption(
            'purge-cms',
            'M',
            InputOption::VALUE_NONE,
            'Get purge CMS page status.'
        );

        $this->addOption(
            'preserve-static',
            'T',
            InputOption::VALUE_NONE,
            'Get preserve static assets on purge status.'
        );

        $this->addOption(
            'use-soft-purge',
            'F',
            InputOption::VALUE_NONE,
            'Get use soft purge status.'
        );

        $this->addOption(
            'geoip-status',
            'G',
            InputOption::VALUE_NONE,
            'Get GeoIP status.'
        );

        $this->addOption(
            'geoip-action',
            'O',
            InputOption::VALUE_NONE,
            'Get GeoIP Action option.'
        );
    }

    /**
     * ConfigGetCommand constructor.
     *
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        parent::__construct();
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine - required by parent class
    {
        $this->output = $output;
        $options = $input->getOptions();

        if (count(array_unique($options)) === 1) {
            $this->output->writeln('<comment>' . $this->getSynopsis() . '</comment>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        // Get Service ID
        if ($input->getOption('service-id')) {
            $this->getServiceID($this->config->getServiceId());
        }

        // Get Token
        if ($input->getOption('token')) {
            $this->getToken($this->config->getApiKey());
        }

        // Get Admin Path Timeout
        if ($input->getOption('admin-path-timeout')) {
            $this->getAdminPathTimeout($this->config->getAdminPathTimeout());
        }

        // Get Stale Content Delivery Time
        if ($input->getOption('stale-content-delivery-time')) {
            $this->getStaleContentDeliveryTime($this->config->getStaleTtl());
        }

        // Get Stale content delivery time in case of backend error
        if ($input->getOption('stale-content-delivery-time-error')) {
            $this->getStaleContentDeliveryTimeError($this->config->getStaleErrorTtl());
        }

        // Get ignored url parameters
        if ($input->getOption('ignored-url-parameters')) {
            $this->getIgnoredUrlParameters($this->config->getIgnoredUrlParameters());
        }

        // Get purge category status
        if ($input->getOption('purge-category')) {
            $this->getPurgeCategory($this->config->canPurgeCatalogCategory());
        }

        // Get purge product status
        if ($input->getOption('purge-product')) {
            $this->getPurgeProduct($this->config->canPurgeCatalogProduct());
        }

        // Get purge CMS page status
        if ($input->getOption('purge-cms')) {
            $this->getPurgeCms($this->config->canPurgeCmsPage());
        }

        // Get preserve static assets on purge status
        if ($input->getOption('preserve-static')) {
            $this->getPreserveStatic($this->config->canPreserveStatic());
        }

        // Get use soft purge status
        if ($input->getOption('use-soft-purge')) {
            $this->getUseSoftPurge($this->config->canUseSoftPurge());
        }

        // Get geoip status
        if ($input->getOption('geoip-status')) {
            $this->getGeoipStatus($this->config->isGeoIpEnabled());
        }

        // Get geoip action
        if ($input->getOption('geoip-action')) {
            $this->getGeoipAction($this->config->getGeoIpAction());
        }

        // Get Fastly status
        if ($input->getOption('fastly-status')) {
            $this->getFastlyStatus($this->config->isFastlyEnabled());
        }
    }

    /**
     * @param $status
     */
    private function getFastlyStatus($status)
    {
        $this->output->writeln(
            'Fastly is ' . $this->processStatus($status),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $serviceId
     */
    private function getServiceID($serviceId)
    {
        $this->output->writeln(
            'Fastly Service ID is <info>' . $serviceId . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $token
     */
    private function getToken($token)
    {
        $this->output->writeln(
            'Fastly API token is <info>' . $token . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $adminPathTimeout
     */
    private function getAdminPathTimeout($adminPathTimeout)
    {
        $this->output->writeln(
            'Admin path timeout is set to <info>' . $adminPathTimeout . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $staleContentDeliveryTime
     */
    private function getStaleContentDeliveryTime($staleContentDeliveryTime)
    {
        $this->output->writeln(
            'Stale content delivery time is set to <info>' . $staleContentDeliveryTime . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $staleContentDeliveryTimeError
     */
    private function getStaleContentDeliveryTimeError($staleContentDeliveryTimeError)
    {
        $this->output->writeln(
            'Stale content delivery time error is set to <info>' . $staleContentDeliveryTimeError . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $ignoredUrlParameters
     */
    private function getIgnoredUrlParameters($ignoredUrlParameters)
    {
        $this->output->writeln(
            'Ignored URl parameters are <info>' . $ignoredUrlParameters . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $purgeCategory
     */
    private function getPurgeCategory($purgeCategory)
    {
        $this->output->writeln(
            'Purge Category is ' . $this->processStatus($purgeCategory),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $purgeProduct
     */
    private function getPurgeProduct($purgeProduct)
    {
        $this->output->writeln(
            'Purge Product is ' . $this->processStatus($purgeProduct),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $purgeCms
     */
    private function getPurgeCms($purgeCms)
    {
        $this->output->writeln(
            'Purge CMS page is ' . $this->processStatus($purgeCms),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $preserveStatic
     */
    private function getPreserveStatic($preserveStatic)
    {
        $this->output->writeln(
            'Preserve static assets on purge is ' . $this->processStatus($preserveStatic),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $useSoftPurge
     */
    private function getUseSoftPurge($useSoftPurge)
    {
        $this->output->writeln(
            'Use Soft Purge is ' . $this->processStatus($useSoftPurge),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $geoipStatus
     */
    private function getGeoipStatus($geoipStatus)
    {
        $this->output->writeln(
            'GeoIP is ' . $this->processStatus($geoipStatus),
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $geoipAction
     */
    private function getGeoipAction($geoipAction)
    {
        $this->output->writeln(
            'GeoIP is set to <info>' . $geoipAction . '</info>',
            OutputInterface::OUTPUT_NORMAL
        );
    }

    /**
     * @param $boolean
     * @return string
     */
    private function processStatus($boolean)
    {
        if ($boolean == 0) {
            return '<info>Disabled</info>';
        } else {
            return '<info>Enabled</info>';
        }
    }
}
