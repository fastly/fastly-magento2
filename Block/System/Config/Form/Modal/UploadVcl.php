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
namespace Fastly\Cdn\Block\System\Config\Form\Modal;

use \Magento\Backend\Block\Template;

/**
 * Class UploadVcl
 *
 * @package Fastly\Cdn\Block\System\Config\Form\Modal
 */
class UploadVcl extends Template
{
    /**
     * Prepare layout
     *
     * @return $this|void
     */
    protected function _prepareLayout() // @codingStandardsIgnoreLine - required by parent class
    {
        parent::_prepareLayout();

        $this->addChild('dialogs', 'Fastly\Cdn\Block\System\Config\Form\Dialogs');
    }

    /**
     * Get dialogs
     *
     * @return string
     */
    public function getDialogsHtml()
    {
        return $this->getChildHtml('dialogs');
    }

    /**
     * Return dialog html
     *
     * @return string
     */
    public function _toHtml()
    {
        return $this->getDialogsHtml();
    }
}
