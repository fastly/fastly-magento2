<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Serialize\Serializer\Json;

class Comparison extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Dir
     */
    private $dir;

    /**
     * Comparison constructor.
     *
     * @param Action\Context $context
     * @param Http $request
     * @param File $file
     * @param Dir $dir
     * @param Json $json
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        Http $request,
        File $file,
        Dir $dir,
        Json $json,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->json = $json;
        $this->file = $file;
        $this->dir = $dir;
    }

    /**
     * @return ResponseInterface|JsonResult|ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $headerVersion = $this->request->getHeader(Config::REQUEST_HEADER, true);
        $etc = $this->dir->getDir($this->request->getControllerModule(), Dir::MODULE_ETC_DIR) . '/';
        $composer = $etc . '../composer.json';
        try {
            $localVersion = $this->json->unserialize($this->file->fileGetContents($composer))['version'];
        } catch (FileSystemException $e) {
            return $result->setData(
                [
                    'status' => false,
                    'msg'   => $e->getMessage()
                ]
            );
        }

        if ($localVersion !== $headerVersion) {
            return $result->setData(
                [
                    'status' => false,
                    'local'  => $localVersion,
                    'header' => $headerVersion,
                    'msg'   => 'Plugin VCL is outdated or VCL was not uploaded! Please click Upload above to correct.'
                ]
            );
        }

        return $result->setData(
            [
                'status'    => true
            ]
        );
    }
}
