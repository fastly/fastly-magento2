<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;

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
            $activeModules = $this->getRequest()->getParam('active_modules');
            $adminTimeout = $this->getRequest()->getParam('admin_timeout');

            $exportAcls = [];
            if (isset($acls)) {
                foreach ($acls as $id => $name) {
                    $aclItems = $this->api->aclItemsList($id);
                    $items = [];
                    foreach ($aclItems as $index => $item) {
                        $items[$index] = [
                            'ip'        => $item->ip,
                            'negated'   => $item->negated,
                            'comment'   => $item->comment,
                            'subnet'    => $item->subnet
                        ];
                    }
                    $exportAcls[$name] = [
                        'items' => $items
                    ];
                }
            }

            $exportDictionaries = [];
            if (isset($dictionaries)) {
                foreach ($dictionaries as $id => $name) {
                    $dictionaryItems = $this->api->dictionaryItemsList($id);
                    $items = [];
                    foreach ($dictionaryItems as $index => $item) {
                        $items[$index] = [
                            'item_key'      => $item->item_key,
                            'item_value'    => $item->item_value
                        ];
                    }
                    $exportDictionaries[$name] = [
                        'items' => $items
                    ];
                }
            }

            $exportAdminTimeout = [];
            if (isset($adminTimeout)) {
                $exportAdminTimeout = [
                    'seconds' => $adminTimeout
                ];
            }

            if (!isset($customSnippets)) {
                $customSnippets = [];
            }

            $exportActiveModules = [];
            if (isset($activeModules)) {
                foreach ($activeModules as $index => $module) {
                    $exportActiveModules[$module['manifest_id']] = json_decode($module['manifest_content'], true);
                }
            }

            $exportData = [
                'edge_acls'         => $exportAcls,
                'edge_dictionaries' => $exportDictionaries,
                'custom_snippets'   => $customSnippets,
                'active_modules'    => $exportActiveModules,
                'admin_timeout'     => $exportAdminTimeout
            ];

            $this->writeJson($exportData, Config::EXPORT_FILE_NAME);

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

    private function writeJson($data, $fileName)
    {
        $write = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $file = $write->getRelativePath($fileName);
        $write->writeFile($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
