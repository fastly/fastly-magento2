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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Waf;

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

/**
 * Class UpdateWafAllowlist
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Waf
 */
class UpdateWafAllowlist extends Action
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
     * UpdateWafAllowlist constructor.
     *
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
     * Upload WAF Bypass snippets
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $service = $this->api->checkServiceDetails();
            $currActiveVersion = $this->vcl->determineVersions($service->versions);

            $snippet = $this->config->getVclSnippets(
                Config::VCL_WAF_PATH,
                Config::VCL_WAF_ALLOWLIST_SNIPPET
            );

            $acls = $this->prepareAcls($this->request->getParam('acls'));

            $allowedItems = $acls;
            $strippedAllowedItems = substr($allowedItems, 0, strrpos($allowedItems, '||', -1));

            // Add WAF bypass snippet
            foreach ($snippet as $key => $value) {
                if ($strippedAllowedItems === '') {
                    $value = '';
                } else {
                    $value = str_replace('####WAF_ALLOWLIST####', $strippedAllowedItems, $value);
                }

                $snippetName = Config::FASTLY_MAGENTO_MODULE . '_waf_' . $key;
                $snippetId = $this->api->getSnippet($currActiveVersion['active_version'], $snippetName)->id;
                $params = [
                    'name'      => $snippetId,
                    'content'   => $value
                ];

                $this->api->updateSnippet($params);
            }

            $this->cacheTypeList->cleanType('config');
            $this->systemConfig->clean();

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
     * Prepares ACL VCL snippets
     *
     * @param $allowedAcls
     * @return string
     */
    private function prepareAcls($allowedAcls)
    {
        $result = '';
        $aclsArray = [];
        $acls = '';

        if ($allowedAcls != null) {
            foreach ($allowedAcls as $key => $value) {
                $aclsArray[] = $value['value'];
            }
            $acls = implode(',', $aclsArray);
        }

        $this->configWriter->save(
            Config::XML_FASTLY_WAF_ALLOW_BY_ACL,
            $acls,
            'default',
            '0'
        );

        if ($acls != '') {
            $allowedAclsPieces = explode(",", $acls);
            foreach ($allowedAclsPieces as $acl) {
                $result .= ' req.http.Fastly-Client-Ip ~ ' . $acl . ' ||';
            }
        }

        return $result;
    }
}
