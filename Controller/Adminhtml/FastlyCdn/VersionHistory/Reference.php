<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

class Reference extends Action
{
    /**
     * @var Api
     */
    private $api;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Action\Context $context,
        Api $api,
        Http $request,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $version = (int)$this->request->getParam('version');
            $response = $this->api->getGeneratedVcl($version);
            if (!$response) {
                throw new \Exception('There is no version #' . $version);
            }
            return $result->setData([
                'status' => true,
                'version' => $response->version,
                'content' => $response->content
            ]);
        } catch (\Exception $exception) {
            return $result->setData([
                'status' => false,
                'msg' => $exception->getMessage()
            ]);
        }
    }
}
