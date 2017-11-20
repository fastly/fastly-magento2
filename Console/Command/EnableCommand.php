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

class EnableCommand extends Command
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Vcl
     */
    protected $vcl;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    protected $_cacheManager;

    /**
     * @var
     */
    protected $_output;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('fastly:conf:set')
            ->setDescription('Enables Fastly as Full Page Cache Caching Application');

        $this->addOption(
            'enable',
            'e',
            InputOption::VALUE_NONE,
            'Enables Fastly Caching.'
        );

        $this->addOption(
            'disable',
            'd',
            InputOption::VALUE_NONE,
            'Disables Fastly Caching and sets build in cache as default cache.'
        );

        $this->addOption(
            'upload-vcl',
            'u',
            InputOption::VALUE_NONE,
            'Uploads default VCL files, Test connection must pass to proceed with VCL uploading. Add --activate argument to activate new version.'
        );

        $this->addOption(
            'activate',
            'a',
            InputOption::VALUE_NONE,
            'Activate newly cloned version. Used with VCL upload.'
        );

        $this->addOption(
            'test-connection',
            'c',
            InputOption::VALUE_NONE,
            'Tests connection with Fastly.'
        );

        $this->addOption(
            'cache',
            'o',
            InputOption::VALUE_NONE,
            'Cleans Config Cache.'
        );

        $this->addOption(
            'service-id',
            's',
            InputOption::VALUE_REQUIRED,
            'Sets Fastly Service ID.',
            false
        );

        $this->addOption(
            'token',
            't',
            InputOption::VALUE_REQUIRED,
            'Sets Fastly Token.',
            false
        );
    }

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config,
        Api $api,
        Vcl $vcl,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Cache\Manager $cacheManager
    ) {
        parent::__construct();
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->_configWriter = $configWriter;
        $this->_cacheManager = $cacheManager;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_output = $output;
        $options = $input->getOptions();

        if(count(array_unique($options)) === 1) {
            $this->_output->writeln('<comment>' . $this->getSynopsis() . '</comment>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        // Set Service ID
        if($input->getOption('service-id')) {
            $this->setServiceID($input->getOption('service-id'));
        }

        // Set Token
        if($input->getOption('token')) {
            $this->setToken($input->getOption('token'));
        }

        // Upload VCL
        if($input->getOption('upload-vcl')) {
            $this->uploadVcl($input->getOption('activate'));
        }

        // Enable
        if($input->getOption('enable')) {
            $this->enableFastly();
        }

        // Disable
        if($input->getOption('disable')) {
            $this->disableFastly();
        }

        // Test Connection
        if($input->getOption('test-connection')) {
            $this->testConnection();
        }

        // Clean Configuration Cache
        if($input->getOption('cache')) {
            $this->cleanCache();
        }

        return;
    }

    /**
     * Enable Fastly
     */
    protected function enableFastly()
    {
        $this->_configWriter->save('system/full_page_cache/caching_application', Config::FASTLY);
        $this->_output->writeln('<info>Fastly Caching Application Activated.</info>', OutputInterface::OUTPUT_NORMAL);
    }

    /**
     * Enable Fastly
     */
    protected function disableFastly()
    {
        $this->_configWriter->save('system/full_page_cache/caching_application', \Magento\PageCache\Model\Config::BUILT_IN);
        $this->_output->writeln('<info>Fastly Caching Application Deactivated, default built in Caching mechanism set.</info>', OutputInterface::OUTPUT_NORMAL);
    }

    /**
     * Set Fastly Service ID
     * @param $serviceId
     */
    protected function setServiceID($serviceId)
    {
        $this->_configWriter->save(Config::XML_FASTLY_SERVICE_ID, $serviceId);
        $this->_output->writeln('<info>Service ID updated.</info>', OutputInterface::OUTPUT_NORMAL);
    }

    /**
     * Set Fastly API Token
     * @param $token
     */
    protected function setToken($token)
    {
        $this->_configWriter->save(Config::XML_FASTLY_API_KEY, $token);
        $this->_output->writeln('<info>Token updated.</info>', OutputInterface::OUTPUT_NORMAL);
    }

    protected function cleanCache()
    {
        $this->_cacheManager->clean([\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER]);
        $this->_output->writeln('<info>Configuration Cache Cleaned.</info>', OutputInterface::OUTPUT_NORMAL);
    }

    /**
     * Upload default VCL, conditions and requests
     * @param $activate
     * @return void
     */
    protected function uploadVcl($activate)
    {
        $service = $this->api->checkServiceDetails();

        if(!$service) {
            $this->_output->writeln('<error>Failed to check Service details. Possible connection issues.</error>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        $currActiveVersion = $this->vcl->determineVersions($service->versions);

        $clone = $this->api->cloneVersion($currActiveVersion['active_version']);

        if(!$clone) {
            $this->_output->writeln('<error>Failed to clone active version.</error>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        $snippets = $this->config->getVclSnippets();

        foreach($snippets as $key => $value)
        {
            $snippetData = array('name' => Config::FASTLY_MAGENTO_MODULE.'_'.$key, 'type' => $key, 'dynamic' => "0", 'priority' => 50, 'content' => $value);
            $status = $this->api->uploadSnippet($clone->number, $snippetData);

            if(!$status) {
                $this->_output->writeln('<error>Failed to upload the Snippet file.</error>', OutputInterface::OUTPUT_NORMAL);
                return;
            }
        }

        $condition = array('name' => Config::FASTLY_MAGENTO_MODULE.'_pass', 'statement' => 'req.http.x-pass', 'type' => 'REQUEST', 'priority' => 90);
        $createCondition = $this->api->createCondition($clone->number, $condition);

        if(!$createCondition) {
            $this->_output->writeln('<error>Failed to create a REQUEST condition.</error>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        $request = array(
            'action' => 'pass',
            'max_stale_age' => 3600,
            'name' => Config::FASTLY_MAGENTO_MODULE.'_request',
            'request_condition' => $createCondition->name,
            'service_id' => $service->id,
            'version' => $currActiveVersion['active_version']
        );

        $createReq = $this->api->createRequest($clone->number, $request);

        if(!$createReq) {
            $this->_output->writeln('<error>Failed to create a REQUEST object.</error>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        $validate = $this->api->validateServiceVersion($clone->number);

        if($validate->status == 'error') {
            $this->_output->writeln('<error>Failed to validate service version: '. $validate->msg . '</error>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        $msg = 'Successfully uploaded VCL. ';

        if($activate) {
            $this->api->activateVersion($clone->number);
            $msg .= 'Activated Version '. $clone->number;
        }

        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            $this->api->sendWebHook('*Upload VCL has been initiated and activated in version ' . $clone->number . '*');
        }

        $this->_output->writeln('<info>' . $msg . '</info>', OutputInterface::OUTPUT_NORMAL);
        return;
    }

    protected function testConnection()
    {
        $service = $this->api->checkServiceDetails();

        if(!$service) {
            $this->_output->writeln('<error>Status: Connection failed, check your Token and Service ID credentials.</error>', OutputInterface::OUTPUT_NORMAL);
            return;
        }

        $currActiveVersion = $this->vcl->determineVersions($service->versions);
        $this->_output->writeln('<info>Status: Connection Successful, current active version: ' . $currActiveVersion['active_version'] . '</info>', OutputInterface::OUTPUT_NORMAL);
        return;
    }
}
