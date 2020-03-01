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
namespace Fastly\Cdn\Block\System\Config\Form;

use Fastly\Cdn\Helper\Data;
use Magento\Backend\Block\Template;
use Fastly\Cdn\Model\Config;

/**
 * Class Dialogs
 *
 * @package Fastly\Cdn\Block\System\Config\Form
 */
class Dialogs extends Template
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Data
     */
    private $helper;

    /**
     * Dialogs constructor.
     *
     * @param Config $config
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Config $config,
        Data $helper,
        Template\Context $context,
        array $data
    ) {
        $this->config = $config;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_template = 'Fastly_Cdn::system/config/dialogs.phtml';

        parent::_construct();
    }

    /**
     * Get Fastly Config model instance
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get Fastly Data helper
     *
     * @return Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
