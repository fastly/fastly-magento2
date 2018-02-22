<?php

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Fastly\Cdn\Model\Config;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ImageBtn extends Field
{
    private $template = 'Fastly_Cdn::system/config/form/field/imageBtn.phtml';

    /**
     * @var Config
     */
    private $config;

    /**
     * ImageBtn constructor.
     *
     * @param Config $config
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Config $config,
        Context $context,
        array $data = []
    ) {
        $this->config = $config;

        parent::__construct($context, $data);
    }

    /**
     * Return element html
     *
     * @return string
     */
    private function getElementHtml()
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('adminhtml/fastlyCdn/vcl/serviceinfo');
    }

    public function getState()
    {
        return $this->config->isImageOptimizationEnabled();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'fastly_push_image_config',
                'label' => __('Enable/Disable'),
            ]
        );
        return $button->toHtml();
    }
}
