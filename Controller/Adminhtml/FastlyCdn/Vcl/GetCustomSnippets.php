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
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param CustomSnippetUpload $customSnippetUpload
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
        try {
            $result = $this->resultJson->create();
            $customSnippetPath = $this->customSnippetUpload->getUploadDirPath('vcl_snippets_custom');
            $directoryRead = $this->readFactory->create($customSnippetPath);
            $customSnippets = $directoryRead->read();

            if (!$customSnippets) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check custom snippet details.'
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
