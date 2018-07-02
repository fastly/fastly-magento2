<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Config\Backend\CustomSnippetUpload;

class DeleteCustomSnippet extends Action
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

    private $customSnippetUpload;

    private $file;

    /**
     * DeleteCustomSnippet constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param CustomSnippetUpload $customSnippetUpload
     * @param File $file
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        CustomSnippetUpload $customSnippetUpload,
        File $file
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->customSnippetUpload = $customSnippetUpload;
        $this->file = $file;

        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $snippet = $this->getRequest()->getParam('snippet_id');
            $customSnippetPath = $this->customSnippetUpload->getUploadDirPath('vcl_snippets_custom');

            if ($this->file->isExists($customSnippetPath . '/' . $snippet)) {
                $this->file->deleteFile($customSnippetPath . '/' . $snippet);
            }
            return $result->setData([
                'status'            => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
