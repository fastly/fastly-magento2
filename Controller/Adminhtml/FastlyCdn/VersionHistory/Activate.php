<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

class Activate extends Action
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
        JsonFactory $jsonFactory,
        Api $api,
        Http $request
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
            $oldVersion = (int)$this->request->getParam('active_version');
            $answer = $this->api->activateVersion($version);
            if (!$answer) {
                throw new \Exception('There is no version #' . $version);
            }

            return $result->setData([
                'old_version' => $oldVersion,
                'version' => $answer->number,
                'comment' => $answer->comment,
                'updated_at' => $answer->updated_at,
                'status' => true
            ]);
        } catch (\Exception $exception) {
            return $result->setData([
                'status' => false,
                'msg' => $exception->getMessage()
            ]);
        }
    }
}
