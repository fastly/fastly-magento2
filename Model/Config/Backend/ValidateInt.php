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

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use Fastly\Cdn\Model\Config;

/**
 * Class ValidateInt
 *
 * @package Fastly\Cdn\Model\Config\Backend
 */
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
            if ($value > $maxValue || $value < 0) {
                throw new LocalizedException(
                    __(
                        '%1 field value must be larger than 0 and smaller or equal to ' . $maxValue,
                        $this->getFieldConfig('label')
                    )
                );
            }
        }

        return $value;
    }
}
