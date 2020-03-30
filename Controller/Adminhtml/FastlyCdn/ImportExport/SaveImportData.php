<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport;

use Exception;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Importer;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

/**
 * Class SaveImportData
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport
 */
class SaveImportData extends Action
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
     * @var Importer
     */
    private $importer;
    /**
     * @var \Fastly\Cdn\Helper\Vcl
     */
    private $vcl;

    /**
     * SaveExportData constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Filesystem $filesystem
     * @param \Fastly\Cdn\Helper\Vcl $vcl
     * @param Importer $importer
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Filesystem $filesystem,
        \Fastly\Cdn\Helper\Vcl $vcl,
        Importer $importer
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->filesystem = $filesystem;
        $this->vcl = $vcl;
        $this->importer = $importer;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $file = $this->getRequest()->getFiles()->get('file');
            $data = json_decode(file_get_contents($file['tmp_name']));
            if (!$data) {
                throw new LocalizedException(__('Invalid file structure'));
            }

            $clone = $this->getClonedVersion();

            $this->importer->importEdgeAcls($clone->number, $data->edge_acls);
            $this->importer->importEdgeDictionaries($clone->number, $data->edge_dictionaries);
            $this->importer->importActiveEdgeModules($clone->number, $data->active_modules);


            if ($this->getRequest()->getParam('activate_flag')) {
                $this->api->activateVersion($clone->number);
            }

            $this->api->addComment($clone->number, ['comment' => 'Magento Module imported multiple configurations.']);

            return $result->setData([
                'status'            => true,
                'active_version'    => $clone->number
            ]);
        } catch (Exception $e) {
            return $result->setData([
                'status' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    protected function getClonedVersion()
    {
        $activeVersion = $this->getRequest()->getParam('active_version');
        $service = $this->api->checkServiceDetails();
        $this->vcl->checkCurrentVersionActive($service->versions, $activeVersion);
        $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);
        return $this->api->cloneVersion($currActiveVersion);
    }
}
