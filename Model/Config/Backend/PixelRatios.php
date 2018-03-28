<?php

namespace Fastly\Cdn\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class PixelRatios
 *
 * @package Fastly\Cdn\Model\Config\Backend
 */
class PixelRatios extends Value
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1.5', 'label'=>__('1.5x')],
            ['value' => '2', 'label'=>__('2x')],
            ['value' => '3', 'label'=>__('3x')],
            ['value' => '3.5', 'label'=>__('3.5x')],
            ['value' => '4', 'label'=>__('4x')]
        ];
    }

    /**
     * @return $this|string
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if ($value === null || empty($value)) {
            throw new LocalizedException(
                __('At least one device pixel ratio must be selected.', $this->getFieldConfig('label'))
            );
        }

        return $value;
    }
}
