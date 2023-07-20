<?php

namespace Fastly\Cdn\Model\Upload;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;

class Dictionary
{

    /**
     * @var Api
     */
    private $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * @param $cloneNumber
     * @param $currActiveVersion
     * @return bool|mixed
     */
    public function setupDictionary($cloneNumber, $currActiveVersion)
    {
        try {
            $dictionaryName = Config::CONFIG_DICTIONARY_NAME;
            $dictionary = $this->api->getSingleDictionary($currActiveVersion, $dictionaryName);

            if (!$dictionary) {
                $params = ['name' => $dictionaryName];
                $dictionary = $this->api->createDictionary($cloneNumber, $params);
            }
            return $dictionary;
        } catch (\Exception $e) {

            return false;
        }
    }
}
