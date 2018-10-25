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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication;

use Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl\CheckAuthSetting;
use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Helper\Vcl;

/**
 * Class Delete
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication
 */
class Delete extends Action
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
     * ForceTls constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;

        parent::__construct($context);
    }

    /**
     * Delete auth
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

            // Check dictionary
            $dictionaryName = Config::AUTH_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($activeVersion, $dictionaryName);

            if ((is_array($dictionary) && empty($dictionary)) || $dictionary == false) {
                return $result->setData([
                    'status'        => false,
                    'not_exists'    => true,
                    'msg'           => 'Authentication dictionary does not exist. Nothing to remove.'
                ]);
            }

            $clone = $this->api->cloneVersion($currActiveVersion);
            $vclPath = Config::VCL_AUTH_SNIPPET_PATH;
            $snippets = $this->config->getVclSnippets($vclPath);

            // Remove snippets
            foreach ($snippets as $key => $value) {
                $name = Config::FASTLY_MAGENTO_MODULE . '_basic_auth_' . $key;
                $this->api->removeSnippet($clone->number, $name);
            }

            $deleteDictionary = $this->api->deleteDictionary($clone->number, $dictionaryName);

            if (!$deleteDictionary) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to delete Auth Dictionary.'
                ]);
            }

            $this->api->validateServiceVersion($clone->number);

            if ($activateVcl === 'true') {
                $this->api->activateVersion($clone->number);
            }

            $comment = ['comment' => 'Magento Module deleted the Basic Authentication dictionary'];
            $this->api->addComment($clone->number, $comment);

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
}
