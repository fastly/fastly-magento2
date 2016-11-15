<?php

namespace Fastly\Cdn\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Vcl extends AbstractHelper
{
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
}