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

use LightnCandy\LightnCandy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ConfigImportCommand
 *
 * @package Fastly\Cdn\Console\Command
 */
class ConfigImportCommand extends Command
{
    /**
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;
    /**
     * @var \Fastly\Cdn\Helper\Vcl
     */
    private $vcl;
    /**
     * @var \Fastly\Cdn\Model\Importer
     */
    private $importer;

    public function __construct(
        \Fastly\Cdn\Model\Api $api,
        \Fastly\Cdn\Helper\Vcl $vcl,
        \Fastly\Cdn\Model\Importer $importer,
        $name = null
    ) {
        parent::__construct($name);
        $this->api = $api;
        $this->vcl = $vcl;
        $this->importer = $importer;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('fastly:conf:import')
            ->setDescription('Import Fastly configuration');

        $this->addOption(
            'activate',
            'a',
            InputOption::VALUE_OPTIONAL,
            'Activate version once improted.',
            false
        );

        $this->addArgument(
            'json',
            InputOption::VALUE_REQUIRED,
            'JSON file with configurations.'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine - required by parent class
    {
        try {
            $file = $input->getArgument('json');
            $data = json_decode(file_get_contents($file));
            if (!$data) {
                $output->writeln("<error>Invalid file structure</error>");
                return;
            }

            $clone = $this->getClonedVersion();

            $output->writeln('Importing Edge ACLs');
            $this->importer->importEdgeAcls($clone->number, $data->edge_acls);

            $output->writeln('Importing Edge Dictionaries');
            $this->importer->importEdgeDictionaries($clone->number, $data->edge_dictionaries);

            $output->writeln('Importing Edge Modules');
            $this->importer->importActiveEdgeModules($clone->number, $data->active_modules);

            if ($input->getOption('activate')) {
                $this->api->activateVersion($clone->number);
            }

            $this->api->addComment($clone->number, ['comment' => 'Magento Module imported multiple configurations.']);

            $output->writeln('<info>Configurations imported successfully.</info>');

        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }

    /**
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getClonedVersion()
    {
        $service = $this->api->checkServiceDetails();
        $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
        return $this->api->cloneVersion($currActiveVersion);
    }
}
