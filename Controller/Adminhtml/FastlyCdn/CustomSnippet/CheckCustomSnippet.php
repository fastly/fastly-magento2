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
namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\CustomSnippet;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

/**
 * Class CheckCustomSnippet
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\CustomSnippet
 */
class CheckCustomSnippet extends Action
{
    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var WriteFactory
     */
    private $writeFactory;
    /**
     * @var JsonFactory
     */
    private $resultJson;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * DeleteCustomSnippet constructor.
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param DirectoryList $directoryList
     * @param WriteFactory $writeFactory
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $filesystem
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        Api $api,
        Vcl $vcl
    ) {
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
        $this->resultJson = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->api = $api;
        $this->vcl = $vcl;

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
            $snippet = $this->getRequest()->getParam('snippet_id');
            $service = $this->api->checkServiceDetails();
            $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);

            $snippetName = explode('_', $snippet);
            $snippetName = explode('.', $snippetName[2]);

            $reqName = Config::FASTLY_MAGENTO_MODULE . '_' . $snippetName[0];
            $checkIfSnippetExist = $this->api->hasSnippet($activeVersion, $reqName);

            return $result->setData([
                'status'    => $checkIfSnippetExist
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
