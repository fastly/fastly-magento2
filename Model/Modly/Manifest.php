<?php

namespace Fastly\Cdn\Model\Modly;

use Magento\Framework\HTTP\Client\Curl;
use Fastly\Cdn\Helper\Manifest as Manifests;

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
     * @var array List of Modly manifests
     */
    private $modlyModules = [];

    /**
     * This is a temp thing
     *
     * @var array
     */
    private $urlList = [
        'https://gist.githubusercontent.com/deninchoo/0f3d7d8fa3668b842962e491964f65c8/raw/7edde40f1b3045b773868253d0d58e731f2a25f9/ttloverride.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/stale.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/normalise.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/hostoverride.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/forcetls.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/error.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/countryblock.json',
        'https://gist.githubusercontent.com/deninchoo/0f3d7d8fa3668b842962e491964f65c8/raw/7e32c1fca5b4e669ed445e078ae55739ef938847/aclblacklist.json',
        'https://gist.githubusercontent.com/deninchoo/0f3d7d8fa3668b842962e491964f65c8/raw/7edde40f1b3045b773868253d0d58e731f2a25f9/esi.json',
        'https://gist.githubusercontent.com/deninchoo/0f3d7d8fa3668b842962e491964f65c8/raw/7edde40f1b3045b773868253d0d58e731f2a25f9/gzip.json',
        'https://gist.githubusercontent.com/deninchoo/0f3d7d8fa3668b842962e491964f65c8/raw/7edde40f1b3045b773868253d0d58e731f2a25f9/nocache.json',
        'https://gist.githubusercontent.com/deninchoo/0f3d7d8fa3668b842962e491964f65c8/raw/7edde40f1b3045b773868253d0d58e731f2a25f9/redirects.json'
        ];

    /**
     * Manifest constructor.
     *
     * @param Curl $curl
     * @param Manifests $manifests
     */
    public function __construct(
        Curl $curl,
        Manifests $manifests
    ) {
        $this->manifests = $manifests;
        $this->curl = $curl;
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
        $manifests = [];
        foreach ($this->urlList as $url) {
            $this->curl->get($url);
            $manifestData = $this->curl->getBody();
            $decodedManifestData = json_decode($manifestData, true);
            $manifests[] = $decodedManifestData;
        }
        return $manifests;
    }
}
