<?php

namespace Fastly\Cdn\Block\System\Config\Form;

use Magento\Backend\Block\Template;
use Fastly\Cdn\Model\Config;

/**
 * Backend rollback dialogs block
 */
class Dialogs extends Template
{
    /**
     * Block's template
     *
     * @var string
     */
    protected $_template = 'Fastly_Cdn::system/config/dialogs.phtml';

    protected $_config;

    public function __construct(Config $config, Template\Context $context, array $data)
    {
        parent::__construct($context, $data);
        $this->_config = $config;
    }

    public function getConfig()
    {
        return $this->_config;
    }
}
