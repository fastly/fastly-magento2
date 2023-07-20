<?php

namespace Fastly\Cdn\Model\Upload;

use Exception;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\Config;

class Acl
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
    public function setupAcl($cloneNumber, $currActiveVersion)
    {
        try {
            $aclName = Config::MAINT_ACL_NAME;
            $acl = $this->api->getSingleAcl($currActiveVersion, $aclName);

            if (!$acl) {
                $params = ['name' => $aclName];
                $acl = $this->api->createAcl($cloneNumber, $params);
            }
            return $acl;
        } catch (Exception $e) {

            return false;
        }
    }

}
