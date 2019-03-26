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

class SuperUserCommand extends Command
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
        $this->setName('fastly:superusers:set')
            ->setDescription('Enables Fastly Super Users for Maintenance mode');

        $this->addOption(
            'enable',
            'e',
            InputOption::VALUE_NONE,
            'Enables Fastly Super Users.'
        );

        $this->addOption(
            'disable',
            'd',
            InputOption::VALUE_NONE,
            'Disables Fastly Super Users.'
        );
    }

    /**
     * SuperUserCommand constructor.
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

    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine - required by parent class
    {
        $this->output = $output;
        $options = $input->getOptions();

        if (count(array_unique($options)) === 1) {
            $this->output->writeln('<comment>' . $this->getSynopsis() . '</comment>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        if ($input->getOption('enable')) {
            $this->toggleSuperUsers('enable');
        }

        if ($input->getOption('disable')) {
            $this->toggleSuperUsers('disable');
        }
    }

    private function toggleSuperUsers($action)
    {
        try {
            $service = $this->api->checkServiceDetails();
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

            $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);
            $msg = 'Super Users have been enabled';

            if (!$dictionary) {
                $msg = 'The required dictionary container does not exist.';
                $this->output->writeln("<error>$msg</error>", OutputInterface::OUTPUT_NORMAL);
                return;
            }

            if ($action == 'enable') {
                $this->api->upsertDictionaryItem(
                    $dictionary->id,
                    Config::CONFIG_DICTIONARY_KEY,
                    1
                );
                $this->sendWebHook('*Super Users have been enabled*');
            } elseif ($action == 'disable') {
                $this->api->upsertDictionaryItem(
                    $dictionary->id,
                    Config::CONFIG_DICTIONARY_KEY,
                    0
                );
                $msg = 'Super Users have been disabled';
                $this->sendWebHook('*Super Users have been disabled*');
            }

            $this->output->writeln('<info>' . $msg . '</info>', OutputInterface::OUTPUT_NORMAL);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->output->writeln("<error>$msg</error>", OutputInterface::OUTPUT_NORMAL);
            return;
        }
    }

    private function sendWebHook($message)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook($message);
        }
    }
}
