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
    protected $resultJson;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    protected $api;

    /**
     * @var Acl
     */
    protected $acl;

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
    )
    {
        $this->resultJson = $resultJsonFactory;
        $this->api = $api;
        $this->acl = $acl;
    }

    public function toOptionArray()
    {
        $service = $this->api->checkServiceDetails();
        $options = [];
        if ($service === false) {
            return $options;
        } else {

            $currActiveVersion = $this->acl->determineVersions($service->versions);
            $acls = $this->api->getAcls($currActiveVersion['active_version']);

            try {
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
}