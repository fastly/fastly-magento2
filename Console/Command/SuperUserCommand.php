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
use Magento\Framework\Console\Cli;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @inheritdoc
     */
    protected function configure() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->setName('fastly:maintenance')
            ->setDescription('Fastly Maintenance Mode configuration');

        $this->addOption(
            'enable',
            'e',
            InputOption::VALUE_NONE,
            'Enables Fastly Maintenance Mode.'
        );

        $this->addOption(
            'disable',
            'd',
            InputOption::VALUE_NONE,
            'Disables Fastly Maintenance Mode.'
        );

        $this->addOption(
            'update',
            'u',
            InputOption::VALUE_NONE,
            'Updates the list of whitelisted IPs. The IP values are supplied by the var/.maintenance.ip file.'
        );
    }

    /**
     * SuperUserCommand constructor.
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param Filesystem $filesystem
     */
    public function __construct(
        Config $config,
        Api $api,
        Vcl $vcl,
        Filesystem $filesystem
    ) {
        parent::__construct();
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->filesystem = $filesystem;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output) // @codingStandardsIgnoreLine - required by parent class
    {
        $this->output = $output;
        $options = $input->getOptions();

        if (count(array_unique($options)) === 1) {
            $this->output->writeln('<comment>' . $this->getSynopsis() . '</comment>', OutputInterface::OUTPUT_NORMAL);
            return Cli::RETURN_FAILURE;
        }

        try {
            if ($input->getOption('enable')) {
                $this->toggleSuperUsers('enable');
            }

            if ($input->getOption('disable')) {
                $this->toggleSuperUsers('disable');
            }

            if ($input->getOption('update')) {
                $this->updateSuIps();
            }
        } catch (\Exception $e) {

            $output->writeln("<error>{$e->getMessage()}</error>");
            return Cli::RETURN_FAILURE;
        }


        return Cli::RETURN_SUCCESS;
    }

    /**
     * @param $action
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function toggleSuperUsers($action)
    {

        $service = $this->api->checkServiceDetails();
        $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

        $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
        $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);
        $msg = 'Maintenance Mode has been enabled';

        if (!$dictionary) {
            $msg = 'The required dictionary container does not exist.';
            throw new \Exception(__($msg));
        }

        if ($action == 'enable') {
            $aclName = Config::MAINT_ACL_NAME;
            $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

            if (!$acl) {
                $msg = 'The required ACL container does not exist. Please re-upload VCL.';
                throw new \Exception(__($msg));
            }

            $hasIps = $this->hasIps($acl);

            if (!$hasIps) {
                $msg = 'Please update Admin IPs list with at least one IP address before enabling Maintenance Mode';
                throw new \Exception(__($msg));
            }

            $this->api->upsertDictionaryItem(
                $dictionary->id,
                Config::CONFIG_DICTIONARY_KEY,
                1
            );
            $this->sendWebHook('*Maintenance Mode has been enabled*');
        } elseif ($action == 'disable') {
            $this->api->upsertDictionaryItem(
                $dictionary->id,
                Config::CONFIG_DICTIONARY_KEY,
                0
            );
            $msg = 'Maintenance Mode has been disabled';
            $this->sendWebHook('*Maintenance Mode has been disabled*');
        }

        $this->output->writeln('<info>' . $msg . '</info>', OutputInterface::OUTPUT_NORMAL);

    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function updateSuIps()
    {
        $service = $this->api->checkServiceDetails();
        $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

        $aclName = Config::MAINT_ACL_NAME;
        $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

        if (!$acl) {
            $msg = 'The required ACL container does not exist. Please re-upload VCL.';
            throw new \Exception(__($msg));
        }

        $ipList = $this->readMaintenanceIp();
        if (!$ipList) {
            $msg = 'Please make sure that the maintenance.ip file contains at least one IP address.';
            throw new \Exception(__($msg));
        }
        $aclId = $acl->id;
        $aclItems = $this->api->aclItemsList($aclId);
        $comment = 'Added for Maintenance Mode';

        $this->deleteIps($aclItems, $aclId);

        $updatedIpList = [];
        foreach ($ipList as $ip) {
            if ($ip[0] == '!') {
                $ip = ltrim($ip, '!');
            }

            // Handle subnet
            $ipParts = explode('/', $ip);
            $subnet = false;
            if (!empty($ipParts[1])) {
                if (is_numeric($ipParts[1]) && (int)$ipParts[1] < 129) {
                    $subnet = $ipParts[1];
                } else {
                    continue;
                }
            }

            if (!filter_var($ipParts[0], FILTER_VALIDATE_IP)) {
                $msg = 'IP validation failed, please make sure that the provided ';
                $msg = $msg . 'IP values are comma-separated and valid.';
                throw new \Exception(__($msg));
            }

            $ipInformation = [
                'op' => 'create',
                'ip' => $ipParts[0],
                'negated' => 0,
                'comment' => $comment
            ];

            if ($subnet) {
                $ipInformation['subnet'] = $subnet;
            }

            $updatedIpList[] = $ipInformation;
        }

        $this->api->bulkAclItems($aclId, $updatedIpList);

        $this->sendWebHook('*Admin IPs list has been updated*');

        $msg = 'Admin IPs list has been updated';
        $this->output->writeln('<info>' . $msg . '</info>', OutputInterface::OUTPUT_NORMAL);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function readMaintenanceIp()
    {
        $flagDir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

        if ($flagDir->isExist('.maintenance.ip')) {
            $temp = $flagDir->readFile('.maintenance.ip');
            $tempList = explode(',', trim($temp));
            foreach ($tempList as $key => $value) {
                if (empty($value) || !trim($value)) {
                    unset($tempList[$key]);
                }
            }
            return $tempList;
        }
        return [];
    }

    /**
     * @param $acl
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function hasIps($acl)
    {
        $aclId = $acl->id;
        $aclItems = $this->api->aclItemsList($aclId);

        if (!$aclItems) {
            return false;
        }
        return true;
    }

    /**
     * @param $aclItems
     * @param $aclId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function deleteIps($aclItems, $aclId)
    {
        $items = [];
        foreach ($aclItems as $item) {
            $items[] = [
                'op' => 'delete',
                'id' => $item->id
            ];
        }
        $this->api->bulkAclItems($aclId, $items);
    }

    /**
     * @param $message
     */
    private function sendWebHook($message)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook($message);
        }
    }
}
