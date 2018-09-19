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
namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Config\Backend\PixelRatios;

/**
 * Class Checkbox
 *
 * @package Fastly\Cdn\Block\System\Config\Form\Field
 */
class Checkbox extends Field
{
    public $values = null;
    public $pixelRatios;
    public $config;

    public function __construct(
        Context $context,
        PixelRatios $pixelRatios,
        Config $config
    ) {
        $this->pixelRatios = $pixelRatios;
        $this->config = $config;
        parent::__construct($context);
    }

    public function _construct()
    {
        $this->_template = 'Fastly_Cdn::system/config/form/field/checkbox.phtml';

        parent::_construct();
    }

    /**
     * Retrieve element HTML markup.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    /**
     * Get pixel ratio values
     *
     * @return array
     */
    public function getValues()
    {
        $values = [];
        $ratios = $this->pixelRatios->toOptionArray();
        foreach ($ratios as $value) {
            $values[$value['value']] = $value['label'];
        }
        return $values;
    }
    /**
     * Get is checked values
     *
     * @param  $name
     * @return boolean
     */
    public function getIsChecked($name)
    {
        return in_array($name, $this->getCheckedValues());
    }

    /**
     * Get checked values
     *
     * @return array|null
     */
    public function getCheckedValues()
    {
        if ($this->values === null) {
            $data = $this->config->getImageOptimizationRatios();
            if (!isset($data)) {
                $data = '';
            }
            $this->values = explode(',', $data);
        }
        return $this->values;
    }
}
