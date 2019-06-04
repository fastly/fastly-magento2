<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class GetExportData
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\ImportExport
 */
class GetExportData extends Action
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
     * @var Vcl
     */
    private $vcl;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * GetExportData constructor.
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl,
        Filesystem $filesystem
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
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
            $service = $this->api->checkServiceDetails();
            $currActiveVersion = $this->vcl->getCurrentVersion($service->versions);

            $read = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $snippetsPath = $read->getRelativePath(Config::CUSTOM_SNIPPET_PATH);
            $absoluteSnippetPath = $read->getAbsolutePath(Config::CUSTOM_SNIPPET_PATH);
            $customSnippets = [];

            if ($read->isExist($absoluteSnippetPath)) {
                $customSnippets = $read->read($snippetsPath);
            }
            $dictionaries = $this->api->getDictionaries($currActiveVersion);
            $acls = $this->api->getAcls($currActiveVersion);

            $snippets = [];
            foreach ($customSnippets as $snippet) {
                $snippetName = explode('/', $snippet)[1];
                if ($read->isExist($snippet)) {
                    $content = $read->readFile($snippet);
                    $snippets[$snippetName] = $content;
                }
            }

            return $result->setData([
                'status'            => true,
                'custom_snippets'   => $snippets,
                'dictionaries'      => $dictionaries,
                'acls'              => $acls
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
