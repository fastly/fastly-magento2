<?php

namespace Fastly\Cdn\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\DeploymentConfig\Reader;

class Vcl extends AbstractHelper
{
    /**
     * @var Reader
     */
    protected $configReader;

    /**
     * Templetize constructor.
     * @param Context $context
     * @param Reader $configReader
     */
    public function __construct(
        Context $context,
        Reader $configReader
    ){
        $this->configReader = $configReader;
        parent::__construct($context);
    }

    /**
     * Determine currently service active version and the next version in which the active version will be cloned
     *
     * @param array $versions
     * @return array
     */
    public function determineVersions(array $versions)
    {
        $activeVersion = null;
        $nextVersion = null;

        if(!empty($versions))
        {
            foreach($versions as $version)
            {
                if($version->active) {
                    $activeVersion = $version->number;
                }
            }

            $nextVersion = (int) end($versions)->number + 1;
        }

        return array('active_version' => $activeVersion, 'next_version' => $nextVersion);
    }

    /**
     * @return mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getAdminFrontName(){
        $config = $this->configReader->load();
        $adminFrontName = $config['backend']['frontName'];
        return $adminFrontName;
    }
}