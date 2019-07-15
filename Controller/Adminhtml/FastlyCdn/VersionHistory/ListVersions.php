<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class ListVersions extends Action
{

    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Api
     */
    private $api;

    public function __construct(

        JsonFactory $jsonFactory,
        Api $api,
        Context $context
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $service = $this->api->getServiceDetails();
            return $result->setData([
                'status' => true,
                'versions' => $service->versions,
                'active_version' => $service->active_version,
                'number_of_pages' => (int)ceil(count($service->versions) / 10),
                'number_of_versions' => (int)count($service->versions)
            ]);
        }catch (\Exception $exception){
            return $result->setData([
                'status' => false,
                'msg' => $exception->getMessage()
            ]);
        }


    }
}
