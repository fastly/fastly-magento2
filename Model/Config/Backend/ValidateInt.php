<?php

namespace Fastly\Cdn\Model\Config\Backend;

class ValidateInt extends \Magento\Framework\App\Config\Value
{
    /**
     * @return $this|string
     * @throws \Exception
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        try {
            if (!ctype_digit($value)){
                $label = $this->getFieldConfig('label');
                $msg = __('%1 field must contain a numeric value.', $label);
                throw new \Exception($msg);
            }
            return $value;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}