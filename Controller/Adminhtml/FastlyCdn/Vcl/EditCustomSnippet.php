<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;

/**
 * Class CreateCustomSnippet
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class EditCustomSnippet extends Action
{
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
     * CreateCustomSnippet constructor.
     *
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param FileFactory $fileFactory
     * @param DirectoryList $directoryList
     * @param WriteFactory $writeFactory
     * @param JsonFactory $resultJsonFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        WriteFactory $writeFactory,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->writeFactory = $writeFactory;
        $this->resultJson = $resultJsonFactory;
        $this->filesystem = $filesystem;

        parent::__construct($context);
    }

    /**
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

            $snippetName = $this->validateCustomSnippet($name, $type, $priority);
            $fileName = $type . '_' . $priority . '_' . $snippetName . '.vcl';

            $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $write->writeFile('/vcl_snippets_custom/' . $fileName, $vcl);

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
     * @param $name
     * @param $type
     * @param $priority
     * @return mixed
     * @throws LocalizedException
     */
    private function validateCustomSnippet($name, $type, $priority)
    {
        $snippetName = str_replace(' ', '', $name);
        $types = ['init', 'recv', 'hit', 'miss', 'pass', 'fetch', 'error', 'deliver', 'log', 'hash', 'none'];

        $inArray = in_array($type, $types);
        $isNumeric = is_numeric($priority);
        $isAlphanumeric = preg_match('/^[\w]+$/', $snippetName);

        if (!$inArray) {
            throw new LocalizedException(__('Type value is not recognised.'));
        }
        if (!$isNumeric) {
            throw new LocalizedException(__('Please make sure that the priority value is a number.'));
        }
        if (!$isAlphanumeric) {
            throw new LocalizedException(__('Please make sure that the name value contains only 
            alphanumeric characters.'));
        }
        return $snippetName;
    }
}
