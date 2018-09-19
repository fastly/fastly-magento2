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
namespace Fastly\Cdn\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Vcl
 *
 * @package Fastly\Cdn\Helper
 */
class Vcl extends AbstractHelper
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
     * Fetch current version
     *
     * @param array $versions
     * @return mixed
     * @throws LocalizedException
     */
    public function getCurrentVersion(array $versions)
    {
        if (!empty($versions)) {
            foreach ($versions as $version) {
                if ($version->active) {
                    return $activeVersion = $version->number;
                }
            }
        }

        throw new LocalizedException(__('Error fetching current version.'));
    }

    /**
     * Fetch next version
     *
     * @param array $versions
     * @return int
     * @throws LocalizedException
     */
    public function getNextVersion(array $versions)
    {
        if (isset(end($versions)->number)) {
            return (int) end($versions)->number + 1;
        }

        throw new LocalizedException(__('Error fetching next version.'));
    }

    /**
     * Check if active versions (local and remote) are in sync
     *
     * @param $versions
     * @param $activeVersion
     * @return bool
     * @throws LocalizedException
     */
    public function checkCurrentVersionActive($versions, $activeVersion)
    {
        $current = $this->getCurrentVersion($versions);

        if ($current != $activeVersion) {
            throw new LocalizedException(__('Active versions mismatch.'));
        }

        return true;
    }

    /**
     * Get the admin front name
     *
     * @return mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getAdminFrontName()
    {
        $config = $this->configReader->load();
        $adminFrontName = $config['backend']['frontName'];

        return $adminFrontName;
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

    /**
     * Fetches and validates active version
     *
     * @param $service
     * @param $activeVersion
     * @return array
     * @throws LocalizedException
     */
    public function getActiveVersion($service, $activeVersion)
    {
        $currActiveVersion = $this->determineVersions($service->versions);
        if ($currActiveVersion['active_version'] != $activeVersion) {
            throw new LocalizedException(__('Active versions mismatch.'));
        }
        return $currActiveVersion;
    }
}
