<?php

namespace Fastly\Cdn\Model\Config\Backend;

/**
 * Class PixelRatios
 *
 * @package Fastly\Cdn\Model\Config\Backend
 */
class PixelRatios
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
}
