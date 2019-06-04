<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class SaveExportData
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport
 */
class SaveExportData extends Action
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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * SaveExportData constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Filesystem $filesystem
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->filesystem = $filesystem;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $acls = $this->getRequest()->getParam('acls');
            $dictionaries = $this->getRequest()->getParam('dictionaries');
            $customSnippets = $this->getRequest()->getParam('custom_snippets');

            $exportAcls = [];
            if (isset($acls)) {
                foreach ($acls as $id => $name) {
                    $aclItems = $this->api->aclItemsList($id);
                    $exportAcls[$name] = [
                        'id'    => $id,
                        'items' => $aclItems
                    ];
                }
            }

            $exportDictionaries = [];
            if (isset($dictionaries)) {
                foreach ($dictionaries as $id => $name) {
                    $dictionaryItems = $this->api->dictionaryItemsList($id);
                    $exportDictionaries[$name] = [
                        'id'    => $id,
                        'items' => $dictionaryItems
                    ];
                }
            }

            if (!isset($customSnippets)) {
                $customSnippets = [];
            }

            $exportData = [
                'edge_acls'         => $exportAcls,
                'edge_dictionaries' => $exportDictionaries,
                'custom_snippets'   => $customSnippets
            ];

            $fileName = Config::EXPORT_FILE_NAME;
            $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $file = $write->getRelativePath($fileName);
            $write->writeFile($file, json_encode($exportData, JSON_PRETTY_PRINT));

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
}
