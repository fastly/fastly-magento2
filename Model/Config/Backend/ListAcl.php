<?php

namespace Fastly\Cdn\Model\Config\Backend;

use \Magento\Framework\Option\ArrayInterface;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Acl;

class ListAcl implements ArrayInterface
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
     * @param JsonFactory $resultJsonFactory
     * @param Api $api
     * @param Acl $acl
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Api $api,
        Acl $acl
    ) {
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->acl = $acl;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        try {
            $service = $this->api->checkServiceDetails();
            if ($service === false) {
                return $options;
            }

            $currActiveVersion = $this->acl->determineVersions($service->versions);
            $acls = $this->api->getAcls($currActiveVersion['active_version']);

            foreach ($acls as $value) {
                $options[] = [
                    'value' => $value->name,
                    'label' => $value->name
                ];
            }
            return $options;
        } catch (\Exception $e) {
            return $options;
        }
    }
}
