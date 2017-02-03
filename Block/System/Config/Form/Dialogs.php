<?php

namespace Fastly\Cdn\Block\System\Config\Form;

use Magento\Backend\Block\Template;
use Fastly\Cdn\Model\Config;


class Dialogs extends Template
{
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'Fastly_Cdn::system/config/dialogs.phtml';

    /**
     * @var Config
     */
    protected $_config;

    /**
     * Dialogs constructor.
     * @param Config $config
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(Config $config, Template\Context $context, array $data)
    {
        parent::__construct($context, $data);
        $this->_config = $config;
    }

    /**
     * Get Fastly Config model instance
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->_config;
    }
}
