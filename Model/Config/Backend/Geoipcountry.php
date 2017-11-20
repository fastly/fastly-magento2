<?php

namespace Fastly\Cdn\Model\Config\Backend;

/**
 * Extending GeoIp to handle serialization/json format difference between M2.2 and above vs prior versions
 *
 * @author Inchoo
 */
class Geoipcountry extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{

    protected $serializer;

    protected function _afterLoad()
    {
        $value = $this->getValue();

        $oldData = @unserialize($value);
        if($oldData) {
            $oldData = (is_array($oldData)) ? $oldData : array();
            $this->setValue(empty($oldData) ? false : $oldData);
        } else {
            $this->setValue(empty($value) ? false : json_decode($value, true));
        }
    }
}
