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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Config\App\Config\Type\System as SystemConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ToggleRateLimiting
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting
 */
class ToggleRateLimiting extends Action
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
     * @var ConfigWriter
     */
    private $configWriter;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * ToggleRateLimiting constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param ConfigWriter $configWriter
     * @param CacheTypeList $cacheTypeList
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        ConfigWriter $configWriter,
        CacheTypeList $cacheTypeList,
        SystemConfig $systemConfig
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->systemConfig = $systemConfig;
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
            $reqName = Config::FASTLY_MAGENTO_MODULE . '_rate_limiting';
            $checkIfReqExist = $this->api->getRequest($activeVersion, $reqName);
            $this->checkDictionary($currActiveVersion, $checkIfReqExist);
            $clone = $this->api->cloneVersion($currActiveVersion);

            $snippet = $this->config->getVclSnippets(
                Config::VCL_RATE_LIMITING_PATH,
                Config::VCL_RATE_LIMITING_SNIPPET
            );

            $condition = [
                'name'      => Config::FASTLY_MAGENTO_MODULE . '_rate_limiting',
                'statement' => 'req.http.Rate-Limit',
                'type'      => 'REQUEST',
                'priority'  => 5
            ];

            $createCondition = $this->api->createCondition($clone->number, $condition);

            if (!$checkIfReqExist) {
                $request = [
                    'name'              => $reqName,
                    'service_id'        => $service->id,
                    'version'           => $currActiveVersion['active_version'],
                    'request_condition' => $createCondition->name,
                    'action'            => 'lookup',
                ];

                $strippedValidPaths = $this->processPaths();

                $this->api->createRequest($clone->number, $request);
                foreach ($snippet as $key => $value) {
                    if ($strippedValidPaths == '') {
                        $value = '';
                    } else {
                        $value = str_replace('####RATE_LIMITED_PATHS####', $strippedValidPaths, $value);
                    }

                    $snippetData = [
                        'name'      => Config::FASTLY_MAGENTO_MODULE . '_rate_limiting_' . $key,
                        'type'      => $key,
                        'dynamic'   => 1,
                        'priority'  => 80,
                        'content'   => $value
                    ];

                    $this->api->uploadSnippet($clone->number, $snippetData);
                }
                $this->uploadSnippets($clone, $currActiveVersion);

                $this->configWriter->save(
                    Config::XML_FASTLY_RATE_LIMITING_ENABLE,
                    1,
                    'default',
                    '0'
                );
            } else {
                $this->api->deleteRequest($clone->number, $reqName);

                foreach ($snippet as $key => $value) {
                    $name = Config::FASTLY_MAGENTO_MODULE . '_rate_limiting_' . $key;
                    if ($this->api->hasSnippet($clone->number, $name) == true) {
                        $this->api->removeSnippet($clone->number, $name);
                    }
                }
                $this->configWriter->save(
                    Config::XML_FASTLY_RATE_LIMITING_ENABLE,
                    0,
                    'default',
                    '0'
                );
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $this->cacheTypeList->cleanType('config');
            $this->systemConfig->clean();

            $this->sendWebHook($checkIfReqExist, $clone);

            $comment = ['comment' => 'Magento Module turned ON Path Protection'];
            if ($checkIfReqExist) {
                $comment = ['comment' => 'Magento Module turned OFF Path Protection'];
            }
            $this->api->addComment($clone->number, $comment);

            return $result->setData([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $clone
     * @param $currActiveVersion
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function uploadSnippets($clone, $currActiveVersion)
    {
        $snippets = $this->config->getVclSnippets(
            Config::VCL_RATE_LIMITING_PATH
        );

        foreach ($snippets as $key => $value) {
            if ($key == 'recv') {
                continue;
            }

            $snippetName = Config::FASTLY_MAGENTO_MODULE . '_rate_limiting_' . $key;
            $dynamic = 0;

            if ($key == 'hash') {
                if ($this->api->hasSnippet($clone->number, $snippetName)) {
                    $snippetId = $this->api->getSnippet($currActiveVersion, $snippetName)->id;
                    $params = [
                        'name'      => $snippetId,
                        'content'   => $value
                    ];
                    $this->api->updateSnippet($params);
                    continue;
                }
                $dynamic = 1;
            }
            $snippetData = [
                'name'      => $snippetName,
                'type'      => $key,
                'dynamic'   => $dynamic,
                'priority'  => 40,
                'content'   => $value
            ];

            $this->api->uploadSnippet($clone->number, $snippetData);
        }
    }

    /**
     * @return bool|string
     */
    private function processPaths()
    {
        $paths = json_decode($this->config->getRateLimitPaths());
        if (!$paths) {
            $paths = [];
        }
        $validPaths = '';

        foreach ($paths as $key => $value) {
            $validPaths .= 'req.url.path ~ "' . $value->path . '" || ';
        }
        $result = substr($validPaths, 0, strrpos($validPaths, '||', -1));

        return $result;
    }

    /**
     * @param $currActiveVersion
     * @param $checkIfReqExist
     * @throws LocalizedException
     */
    private function checkDictionary($currActiveVersion, $checkIfReqExist)
    {
        if (!$checkIfReqExist) {
            $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);
            if (!$dictionary) {
                throw new LocalizedException(__(
                    'The required dictionary container does not exist. Please re-upload VCL.'
                ));
            }
        }
    }

    /**
     * @param $checkIfReqExist
     * @param $clone
     */
    private function sendWebHook($checkIfReqExist, $clone)
    {
        if ($this->config->areWebHooksEnabled() && $this->config->canPublishConfigChanges()) {
            if ($checkIfReqExist) {
                $this->api->sendWebHook('*Path Protection has been turned OFF in Fastly version '
                    . $clone->number . '*');
            } else {
                $this->api->sendWebHook('*Path Protection has been turned ON in Fastly version '
                    . $clone->number . '*');
            }
        }
    }
}
