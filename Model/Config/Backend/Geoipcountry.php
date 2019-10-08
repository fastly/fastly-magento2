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

use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class Geoipcountry
 *
 * Extending GeoIp to handle serialization/json format difference between M2.2 and above vs prior versions
 * @package Fastly\Cdn\Model\Config\Backend
 */
class Geoipcountry extends ArraySerialized
{
    /**
     * @var SerializerInterface
     */
    private $serializerInterface;
    /**
     * Geoipcountry constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param SerializerInterface $serializerInterface
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        SerializerInterface $serializerInterface,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        $this->serializerInterface = $serializerInterface;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );
    }

    protected function _afterLoad() // @codingStandardsIgnoreLine - required by parent class
    {
        $value = $this->getValue();

        $oldData = json_decode($value, true);
        if (!$oldData) {
            try {
                $oldData = $this->serializerInterface->unserialize($value);
            } catch (\Exception $e) {
                $oldData = false;
            }
        }
        if ($oldData) {
            $oldData = (is_array($oldData)) ? $oldData : [];
            $this->setValue(empty($oldData) ? false : $oldData);
        } else {
            $this->setValue(empty($value) ? false : json_decode($value, true));
        }
    }
}
