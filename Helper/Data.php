<?php

namespace Fastly\Cdn\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Module\ModuleListInterface;

class Data extends AbstractHelper
{
    const FASTLY_MODULE_NAME = 'Fastly_Cdn';

    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * Data constructor.
     * @param Context $context
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList)
    {
        $this->_moduleList = $moduleList;
        parent::__construct($context);
    }

    /**
     * Return Fastly module version
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->_moduleList->getOne(self::FASTLY_MODULE_NAME)['setup_version'];
    }
}