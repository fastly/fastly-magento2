<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Fastly\Cdn\Model\Config\Backend\CustomSnippetUpload;
use \Magento\Framework\Filesystem\Directory\ReadFactory;

/**
 * Class GetCustomSnippets
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class GetCustomSnippets extends Action
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
     * @var CustomSnippetUpload
     */
    private $customSnippetUpload;

    private $readFactory;

    /**
     * GetCustomSnippets constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param CustomSnippetUpload $customSnippetUpload
     * @param ReadFactory $readFactory
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        CustomSnippetUpload $customSnippetUpload,
        ReadFactory $readFactory
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->customSnippetUpload = $customSnippetUpload;
        $this->readFactory = $readFactory;
        parent::__construct($context);
    }

    /**
     * Get all backends for active version
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $customSnippetPath = $this->customSnippetUpload->getUploadDirPath('vcl_snippets_custom');
            $directoryRead = $this->readFactory->create($customSnippetPath);
            $customSnippets = $directoryRead->read();

            if (!$customSnippets) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'No snippets found.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'snippets'  => $customSnippets
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
