<?php

namespace Fastly\Cdn\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\DeploymentConfig\Reader;

class Acl extends AbstractHelper
{
    /**
     * @var Reader
     */
    private $configReader;

    /**
     * Templetize constructor.
     * @param Context $context
     * @param Reader $configReader
     */
    public function __construct(
        Context $context,
        Reader $configReader
    ) {
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

        if (!empty($versions)) {
            foreach ($versions as $version) {
                if ($version->active) {
                    $activeVersion = $version->number;
                }
            }

            $nextVersion = (int) end($versions)->number + 1;
        }

        return [
            'active_version'    => $activeVersion,
            'next_version'      => $nextVersion
        ];
    }
}
