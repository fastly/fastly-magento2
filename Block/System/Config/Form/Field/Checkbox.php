<?php

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
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    /**
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
     *
     * @param  $name
     * @return boolean
     */
    public function getIsChecked($name)
    {
        return in_array($name, $this->getCheckedValues());
    }

    /**
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
