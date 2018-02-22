<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Acl;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Acl;

class GetAcl extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * GetAcl constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Acl $acl
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Api $api,
        Acl $acl
    ) {
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->acl = $acl;
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
            if (!$service) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Failed to check Service details.'
                ]);
            }

            return $result->$this->acl->determineVersions($service->versions);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
