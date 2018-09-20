<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Model\Config\Backend;

use \Magento\Framework\Option\ArrayInterface;
use \Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Acl;

/**
 * Class ListAcl
 *
 * @package Fastly\Cdn\Model\Config\Backend
 */
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
