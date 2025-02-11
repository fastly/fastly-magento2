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

use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteFactory;

/**
 * Class CreateCustomSnippet
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\CustomSnippet
 */
class CreateCustomSnippet extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var RawFactory
     */
    private $resultRawFactory;
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

    protected const CUSTOM_VCL_TYPES = [
        'init',
        'recv',
        'hash',
        'hit',
        'miss',
        'pass',
        'fetch',
        'error',
        'deliver',
        'log'
    ];

    /**
     * CreateCustomSnippet constructor.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param FileFactory $fileFactory
     * @param DirectoryList $directoryList
     * @param WriteFactory $writeFactory
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $filesystem
     * @param Config $config
     * @param Vcl $vcl
     * @param Api $api
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        Config $config,
        Vcl $vcl,
        Api $api
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
        $this->resultJson = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->config = $config;
        $this->vcl = $vcl;
        $this->api = $api;

        parent::__construct($context);
    }

    /**
     * Validates the custom snippet data and writes the custom snippet VCL file
     *
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $name = $this->getRequest()->getParam('name');
            $type = $this->getRequest()->getParam('type');
            $priority = $this->getRequest()->getParam('priority');
            $vcl = $this->getRequest()->getParam('vcl');
            $edit = $this->getRequest()->getParam('edit');
            $original = basename($this->getRequest()->getParam('original') ?? "");
            $validation = $this->config->validateCustomSnippet($name, $type, $priority);
            $error = $validation['error'];
            if ($error != null) {
                throw new LocalizedException(__($error));
            }
            $snippetName = $validation['snippet_name'];

            $fileName = $type . '_' . $priority . '_' . $snippetName . '.vcl';

            $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $snippetPath = $write->getRelativePath(Config::CUSTOM_SNIPPET_PATH . $fileName);

            // Allow if editing existing snippet and the name is unchanged, otherwise check if snippet already exists.
            if (!($edit === 'true' && $this->nameUnchanged($snippetName, $original))
                && $this->snippetExists($snippetName, $write)
            ) {
                throw new LocalizedException(__('Custom snippet \'%1\' already exists', $snippetName));
            }

            if ($edit === 'true') {
                $originalPath = $write->getRelativePath(Config::CUSTOM_SNIPPET_PATH . $original);
                $write->renameFile($originalPath, $snippetPath);
            }

            $write->writeFile($snippetPath, $vcl);

            return $result->setData([
                'status'    => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $snippetName
     * @param ReadInterface $directoryRead
     * @return bool
     * @throws LocalizedException
     */
    private function snippetExists(string $snippetName, ReadInterface $directoryRead): bool
    {
        $searchPattern = sprintf(
            '{%s}_*_%s.vcl',
            implode(',', self::CUSTOM_VCL_TYPES),
            $snippetName
        );
        $snippetOnDisk = $directoryRead->search(
            $searchPattern,
            Config::CUSTOM_SNIPPET_PATH
        );

        if (count($snippetOnDisk)) {
            return true;
        }

        $service = $this->api->checkServiceDetails();
        $versions = $this->vcl->determineVersions($service->versions);

        $activeVersion = $versions['active_version'];

        if (!$activeVersion) {
            return false;
        }

        return $this->api->hasSnippet(
            $activeVersion,
            Config::FASTLY_MAGENTO_MODULE . '_' . $snippetName
        );
    }

    /**
     * @param string $name
     * @param string $original
     * @return bool
     */
    private function nameUnchanged(string $name, string $original): bool
    {
        $searchPattern = sprintf(
            '/(%s)_[0-9]+_%s.vcl/',
            implode('|', self::CUSTOM_VCL_TYPES),
            $name
        );
        return preg_match($searchPattern, $original) === 1;
    }
}
