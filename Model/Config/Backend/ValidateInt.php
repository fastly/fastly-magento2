<?php

namespace Fastly\Cdn\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class ValidateInt extends Value
{
    /**
     * @return $this|string
     * @throws \Exception
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (ctype_digit($value) === false) {
            throw new LocalizedException(
                __('%1 field must contain a numeric value.', $this->getFieldConfig('label'))
            );
        }

        return $value;
    }
}
