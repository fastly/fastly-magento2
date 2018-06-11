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
     * Returns a list of Modly manifests
     *
     * @return array
     */
    public function getModlyManifests()
    {
        $manifests = $this->manifests->getManifests();
        return $manifests;
    }

    /**
     * Fetches the fresh data from source
     */
    public function reloadModlyManifest()
    {
        foreach ($this->urlList as $modlyUrl) {
            $encodedContent = $this->fetchManifest($modlyUrl);
            $decodedContent = json_decode($encodedContent, true);
            $content = ['content' => $encodedContent];
            $this->modlyModules[] = array_merge($decodedContent, $content);
        }
    }

    private function fetchManifest($url)
    {
        $this->curl->get($url);

        $content = $this->curl->getBody();
        return $content;
    }
}
