<?php

namespace Fastly\Cdn\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Fastly\Cdn\Model\Config;

class ValidateInt extends Value
{
    /**
     * @return $this|string
     * @throws \Exception
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $field = $this->getField();

        if (ctype_digit($value) === false) {
            throw new LocalizedException(
                __('%1 field must contain a numeric value.', $this->getFieldConfig('label'))
            );
        }

        if ($field == 'admin_path_timeout') {
            $maxValue = Config::XML_FASTLY_MAX_FIRST_BYTE_TIMEOUT;
            if ($value > $maxValue) {
                throw new LocalizedException(
                    __('%1 field value must not be greater than ' . $maxValue, $this->getFieldConfig('label'))
                );
            }
        }

        return $value;
    }
}
