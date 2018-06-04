<?php

namespace Fastly\Cdn\Model\Modly;

use Magento\Framework\HTTP\Client\Curl;

class Manifest
{
    /**
     * @var Curl
     */
    private $curl;

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
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/ttl.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/stale.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/normalise.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/hostoverride.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/forcetls.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/error.json',
        'https://gist.githubusercontent.com/udovicic/893e416af84f4e914697b1698e72485c/raw/1b0f018bc697d171cd4e5bea077740605d5cacf6/countryblock.json'
    ];

    /**
     * Manifest constructor.
     *
     * @param Curl $curl
     */
    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    /**
     * Returns list of Modly modules manifests
     *
     * @return array
     */
    public function getModlyModules()
    {
        $this->reloadModlyManifest();

        return $this->modlyModules;
    }

    /**
     * Fetches the fresh data from source
     */
    public function reloadModlyManifest()
    {
        foreach ($this->urlList as $modlyUrl) {
            $this->modlyModules[] = $this->fetchManifest($modlyUrl);
        }
    }

    private function fetchManifest($url)
    {
        $this->curl->get($url);

        $content = $this->curl->getBody();

        return json_decode($content, true);
    }
}