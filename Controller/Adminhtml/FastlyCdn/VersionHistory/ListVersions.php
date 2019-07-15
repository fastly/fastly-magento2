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
        //todo: paginacija i try caches (vidi u starim fileovima)
        $result = $this->jsonFactory->create();
        $service = $this->api->getServiceDetails();
        return $result->setData([
            'status' => true,
            'versions' => $service->versions,
            'active_version' => $service->active_version,
            'number_of_pages' => ceil(count($service->versions) / 10),
            'number_of_versions' => count($service->versions)
        ]);
    }
}
