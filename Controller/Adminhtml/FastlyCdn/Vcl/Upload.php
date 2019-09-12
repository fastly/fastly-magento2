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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Config\Backend\CustomSnippetUpload;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Config\Model\ResourceModel\Config as CoreConfig;

/**
 * Class Upload
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class Upload extends Action
{
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $resultJson;
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
     * @var CustomSnippetUpload
     */
    private $customSnippetUpload;
    /**
     * @var DateTime
     */
    private $time;
    /**
     * @var TimezoneInterface
     */
    private $timezone;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var CoreConfig
     */
    private $coreConfig;

    /**
     * Upload constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param CustomSnippetUpload $customSnippetUpload
     * @param DateTime $time
     * @param TimezoneInterface $timezone
     * @param Filesystem $filesystem
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        CustomSnippetUpload $customSnippetUpload,
        DateTime $time,
        TimezoneInterface $timezone,
        Filesystem $filesystem,
        CoreConfig $coreConfig
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->customSnippetUpload = $customSnippetUpload;
        $this->time = $time;
        $this->timezone = $timezone;
        $this->filesystem = $filesystem;
        parent::__construct($context);
        $this->coreConfig = $coreConfig;
    }

    /**
     * Upload VCL snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $activateVcl = $this->getRequest()->getParam('activate_flag');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
            $clone = $this->api->cloneVersion($currActiveVersion);
            $snippets = $this->config->getVclSnippets();
            $read = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $customSnippetPath = $read->getAbsolutePath(Config::CUSTOM_SNIPPET_PATH);
            $customSnippets = $this->config->getCustomSnippets($customSnippetPath);

            foreach ($snippets as $key => $value) {
                $priority = 50;
                if ($key == 'hash') {
                    $priority = 80;
                }
                $snippetData = [
                    'name'      => Config::FASTLY_MAGENTO_MODULE . '_' . $key,
                    'type'      => $key,
                    'dynamic'   => "0",
                    'priority'  => $priority,
                    'content'   => $value
                ];
                $this->api->uploadSnippet($clone->number, $snippetData);
            }

            foreach ($customSnippets as $key => $value) {
                $snippetNameData = $this->validateCustomSnippet($key);
                $snippetType = $snippetNameData[0];
                $snippetPriority = $snippetNameData[1];
                $snippetShortName = $snippetNameData[2];

                $customSnippetData = [
                    'name'      => Config::FASTLY_MAGENTO_MODULE . '_' . $snippetShortName,
                    'type'      => $snippetType,
                    'priority'  => $snippetPriority,
                    'content'   => $value,
                    'dynamic'   => '0'
                ];
                $this->api->uploadSnippet($clone->number, $customSnippetData);
            }

            $this->createGzipHeader($clone);

            $condition = [
                'name'      => Config::FASTLY_MAGENTO_MODULE . '_pass',
                'statement' => 'req.http.x-pass',
                'type'      => 'REQUEST',
                'priority'  => 90
            ];
            $createCondition = $this->api->createCondition($clone->number, $condition);
            $request = [
                'action'            => 'pass',
                'max_stale_age'     => 3600,
                'name'              => Config::FASTLY_MAGENTO_MODULE . '_request',
                'request_condition' => $createCondition->name,
                'service_id'        => $service->id,
                'version'           => $currActiveVersion
            ];

            $this->api->createRequest($clone->number, $request);
            $dictionary = $this->setupDictionary($clone->number, $currActiveVersion);
            $acl = $this->setupAcl($clone->number, $currActiveVersion);

            if (!$dictionary || !$acl) {
                throw new LocalizedException(__('Failed to create Containers'));
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                $this->api->sendWebHook(
                    '*Upload VCL has been initiated and activated in version ' . $clone->number . '*'
                );
            }

            $comment = ['comment' => 'Magento Module uploaded VCL'];
            $this->api->addComment($clone->number, $comment);
            $this->coreConfig->saveConfig(Config::UPDATED_VCL_FLAG, 1);
            return $result->setData([
                'status'            => true,
                'active_version'    => $clone->number
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate custom snippet naming convention
     * [vcl_snippet_type]_[priority]_[short_name_description].vcl
     *
     * @param $customSnippet
     * @return array
     * @throws LocalizedException
     */
    private function validateCustomSnippet($customSnippet)
    {
        $snippetName = str_replace(' ', '', $customSnippet);
        $snippetNameData = explode('_', $snippetName, 3);
        $containsEmpty = in_array("", $snippetNameData, true);
        $types = ['init', 'recv', 'hit', 'miss', 'pass', 'fetch', 'error', 'log', 'deliver', 'hash', 'none'];
        $exception = 'Failed to upload VCL snippets. Please make sure the custom VCL snippets 
            follow this naming convention: [vcl_snippet_type]_[priority]_[short_name_description].vcl';

        if (count($snippetNameData) < 3) {
            throw new LocalizedException(__($exception));
        }

        $inArray = in_array($snippetNameData[0], $types);
        $isNumeric = is_numeric($snippetNameData[1]);
        $isAlphanumeric = preg_match('/^[\w]+$/', $snippetNameData[2]);

        if ($containsEmpty || !$inArray || !$isNumeric || !$isAlphanumeric) {
            throw new LocalizedException(__($exception));
        }
        return $snippetNameData;
    }

    /**
     * @param $cloneNumber
     * @param $currActiveVersion
     * @return bool|mixed
     * @throws LocalizedException
     */
    private function setupDictionary($cloneNumber, $currActiveVersion)
    {
        $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
        $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);

        if (!$dictionary) {
            $params = ['name' => $dictionaryName];
            $dictionary = $this->api->createDictionary($cloneNumber, $params);
        }
        return $dictionary;
    }

    /**
     * @param $cloneNumber
     * @param $currActiveVersion
     * @return bool|mixed
     * @throws LocalizedException
     */
    private function setupAcl($cloneNumber, $currActiveVersion)
    {
        $aclName = Config::MAINT_ACL_NAME;
        $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

        if (!$acl) {
            $params = ['name' => $aclName];
            $acl = $this->api->createAcl($cloneNumber, $params);
        }
        return $acl;
    }

    /**
     * @param $clone
     * @throws LocalizedException
     */
    private function createGzipHeader($clone)
    {
        $condition = [
            'name'      => Config::FASTLY_MAGENTO_MODULE . '_gzip_safety',
            'statement' => 'beresp.http.x-esi',
            'type'      => 'CACHE',
            'priority'  => 100
        ];
        $createCondition = $this->api->createCondition($clone->number, $condition);

        $headerData = [
            'name'              => Config::FASTLY_MAGENTO_MODULE . '_gzip_safety',
            'type'              => 'cache',
            'dst'               => 'gzip',
            'action'            => 'set',
            'priority'          => 1000,
            'src'               => 'false',
            'cache_condition'   => $createCondition->name,
        ];

        $this->api->createHeader($clone->number, $headerData);
    }
}
