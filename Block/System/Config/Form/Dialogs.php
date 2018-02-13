<?php

namespace Fastly\Cdn\Block\System\Config\Form;

use Magento\Backend\Block\Template;
use Fastly\Cdn\Model\Config;

class Dialogs extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Dialogs constructor.
     * @param Config $config
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(Config $config, Template\Context $context, array $data)
    {
        $this->config = $config;

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
}
