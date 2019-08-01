<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;

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
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * Comparison constructor.
     * @param Action\Context $context
     * @param Http $request
     * @param JsonFactory $jsonFactory
     * @param Filesystem $filesystem
     * @param Reader $reader
     * @param Data $jsonHelper
     */
    public function __construct(
        Action\Context $context,
        Http $request,
        JsonFactory $jsonFactory,
        Filesystem $filesystem,
        Reader $reader,
        Data $jsonHelper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->reader = $reader;
        $this->jsonHelper = $jsonHelper;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $vclVersion = $this->request->getHeader(Config::REQUEST_HEADER);
        $module = $this->reader->getModuleDir(Dir::MODULE_ETC_DIR, Config::FASTLY_MODULE_NAME) . '/../';
        $composer = $this->filesystem->getDirectoryReadByPath($module)->readFile('composer.json');
        $composer = $this->jsonHelper->jsonDecode($composer);
        if ($vclVersion != $composer['version']) {
            return $result->setData([
                'status' => false,
                'msg'   => 'Plugin VCL version is outdated! Please re-Upload.'
            ]);
        }
        return $result->setData([
            'status'    => true
        ]);
    }
}
