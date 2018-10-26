<?php

namespace Fastly\Cdn\Model\Modly;

use Magento\Framework\HTTP\Client\Curl;
use Fastly\Cdn\Helper\Manifest as Manifests;
use Magento\Framework\Filesystem;
use Fastly\Cdn\Model\Config;

class Manifest
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Manifests
     */
    private $manifests;

    /**
     * This is a temp thing
     *
     * @var array
     */
    private $urlList = [];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Config
     */
    private $config;

    /**
     * Manifest constructor.
     *
     * @param Curl $curl
     * @param Manifests $manifests
     * @param Filesystem $filesystem
     * @param Config $config
     */
    public function __construct(
        Curl $curl,
        Manifests $manifests,
        Filesystem $filesystem,
        Config $config
    ) {
        $this->manifests = $manifests;
        $this->curl = $curl;
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    /**
     * Returns a list of Modly modules and their data stored in the database
     *
     * @return array
     */
    public function getAllModlyManifests()
    {
        $manifests = $this->manifests->getAllModules();
        return $manifests;
    }

    public function getActiveModlyManifests()
    {
        $manifests = $this->manifests->getActiveModules();
        return $manifests;
    }

    public function getModule($id)
    {
        $module = $this->manifests->getModuleData($id);
        return $module;
    }

    /**
     * Fetch all manifests from the repository
     *
     * @return array
     */
    public function getAllRepoManifests()
    {
        $fastlyEdgeModules = $this->config->getFastlyEdgeModules();
        $manifests = [];

        foreach ($fastlyEdgeModules as $key => $value) {
            $decodedManifestData = json_decode($value, true);
            $manifests[] = $decodedManifestData;
        }
        return $manifests;
    }
}
