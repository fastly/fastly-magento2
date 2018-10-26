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

use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class CheckAuthSetting
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\BasicAuthentication
 */
class CheckAuthSetting extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * CheckTlsSetting constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param Api $api
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        Api $api,
        JsonFactory $resultJsonFactory
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    /**
     * Verifies weather or not auth settings snippet exists on specified Fastly version
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $activeVersion = $this->getRequest()->getParam('active_version');
            $snippets = $this->config->getVclSnippets(Config::VCL_AUTH_SNIPPET_PATH);

            foreach ($snippets as $key => $value) {
                $name = Config::FASTLY_MAGENTO_MODULE . '_basic_auth_' . $key;
                $status = $this->api->hasSnippet($activeVersion, $name);

                if ($status == false) {
                    return $result->setData([
                        'status' => false
                    ]);
                }
            }

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
}
