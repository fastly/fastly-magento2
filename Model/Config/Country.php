<?php

namespace Fastly\Cdn\Model\Config;

class Country extends \Magento\Directory\Model\Config\Source\Country
{

    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        $options = parent::toOptionArray($isMultiselect, $foregroundCountries);

        // Introduced custom class so we can update countries list if there is a mismatch between Magento and Fastly options
        $additionalCountries = [
            [
                'value' => 'PR',
                'label' => 'Puerto Rico',
            ]
        ];

        $options = array_merge($options, $additionalCountries);
        return $options;
    }
}
