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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Maintenance;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class ToggleSuSetting
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Maintenance
 */
class ToggleSuSetting extends Action
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * ToggleSuSetting constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        Filesystem $filesystem
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->filesystem = $filesystem;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
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
            $reqName = Config::FASTLY_MAGENTO_MODULE . '_maintenance';
            $checkIfReqExist = $this->api->getRequest($activeVersion, $reqName);
            $snippets = $this->config->getVclSnippets(Config::VCL_MAINT_SNIPPET_PATH);
            $configDictionaryValue = 1;

            $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);

            $aclName = Config::MAINT_ACL_NAME;
            $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

            if (!$checkIfReqExist) {
                $dictionary = $this->createConfigDictionary($clone->number, $dictionaryName, $dictionary);
                if (!$dictionary) {
                    throw new LocalizedException(__('Failed to create Dictionary Container'));
                }

                $acl = $this->createSuAcl($currActiveVersion, $clone->number, $aclName);
                if (!$acl) {
                    throw new LocalizedException(__('Failed to create ACL Container'));
                }

                $request = [
                    'name'          => $reqName,
                    'service_id'    => $service->id,
                    'version'       => $currActiveVersion,
                    'force_ssl'     => true
                ];

                $this->api->createRequest($clone->number, $request);

                // Add maintenance snippet
                foreach ($snippets as $key => $value) {
                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE . '_maintenance_' . $key,
                        'type'      => $key,
                        'dynamic'   => "0",
                        'priority'  => 10,
                        'content'   => $value
                    ];
                    $this->api->uploadSnippet($clone->number, $snippetData);
                }
            } else {
                $this->api->deleteRequest($clone->number, $reqName);
                $configDictionaryValue = 0;

                // Remove maintenance snippet
                foreach ($snippets as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE.'_maintenance_'.$key;
                    $this->api->removeSnippet($clone->number, $name);
                }
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $this->api->upsertDictionaryItem(
                $dictionary->id,
                Config::CONFIG_DICTIONARY_KEY,
                $configDictionaryValue
            );

            $this->updateAclItems($acl->id);

            if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
                if ($checkIfReqExist) {
                    $this->api->sendWebHook(
                        '*Super Users have been turned OFF in Fastly version '. $clone->number . '*'
                    );
                } else {
                    $this->api->sendWebHook(
                        '*Super Users have been turned ON in Fastly version '. $clone->number . '*'
                    );
                }
            }

            $comment = ['comment' => 'Magento Module turned ON Super Users'];
            if ($checkIfReqExist) {
                $comment = ['comment' => 'Magento Module turned OFF Super Users'];
            }
            $this->api->addComment($clone->number, $comment);

            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $cloneNumber
     * @param $dictionaryName
     * @param $dictionary
     * @return bool|mixed
     * @throws LocalizedException
     */
    private function createConfigDictionary($cloneNumber, $dictionaryName, $dictionary)
    {
        if ((is_array($dictionary) && empty($dictionary)) || $dictionary == false) {
            $params = ['name' => $dictionaryName];
            $dictionary = $this->api->createDictionary($cloneNumber, $params);

            if (!$dictionary) {
                return false;
            }
        }
        return $dictionary;
    }

    /**
     * @param $currActiveVersion
     * @param $cloneNumber
     * @param $aclName
     * @return bool|mixed
     * @throws LocalizedException
     */
    private function createSuAcl($currActiveVersion, $cloneNumber, $aclName)
    {
        $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

        if (!$acl) {
            $params = ['name' => $aclName];
            $acl = $this->api->createAcl($cloneNumber, $params);

            if (!$acl) {
                return false;
            }
        }
        return $acl;
    }

    /**
     * @param $aclId
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function updateAclItems($aclId)
    {
        $ipList = $this->readMaintenanceIp();
        $aclItems = $this->api->aclItemsList($aclId);
        $comment = 'Added for Maintenance Support';

        foreach ($aclItems as $key => $value) {
            $this->api->deleteAclItem($aclId, $value->id);
        }

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
                continue;
            }

            $this->api->upsertAclItem($aclId, $ipParts[0], 0, $comment, $subnet);
        }
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
            return explode(',', trim($temp));
        }
        return [];
    }
}
