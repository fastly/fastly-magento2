<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Blocking;

use Fastly\Cdn\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

abstract class AbstractBlocking extends Action
{
    protected ConfigWriter $configWriter;

    public function __construct(
        Context $context,
        ConfigWriter $configWriter
    ) {
        $this->configWriter = $configWriter;

        parent::__construct($context);
    }

    /**
     * Prepares ACL VCL snippets
     *
     * @param $blockedAcls
     * @return string
     */
    protected function prepareAcls($blockedAcls)
    {
        $result = '';
        $aclsArray = [];
        $acls = '';

        if ($blockedAcls != null) {
            foreach ($blockedAcls as $key => $value) {
                $aclsArray[] = $value['value'];
            }
            $acls = implode(',', $aclsArray);
        }

        $this->configWriter->save(
            Config::XML_FASTLY_BLOCK_BY_ACL,
            $acls,
            'default',
            '0'
        );

        if ($acls != '') {
            $blockedAclsPieces = explode(",", $acls);
            foreach ($blockedAclsPieces as $acl) {
                $result .= ' req.http.Fastly-Client-Ip ~ ' . $acl . ' ||';
            }
        }

        return $result;
    }

    /**
     * Prepares blocked countries VCL snippet
     *
     * @param $blockedCountries
     * @return string
     */
    protected function prepareCountryCodes($blockedCountries)
    {
        $result = '';
        $countriesArray = [];
        $countries = '';

        if ($blockedCountries != null) {
            foreach ($blockedCountries as $key => $value) {
                $countriesArray[] = $value['value'];
            }
            $countries = implode(',', $countriesArray);
        }

        $this->configWriter->save(
            Config::XML_FASTLY_BLOCK_BY_COUNTRY,
            $countries,
            'default',
            '0'
        );

        if ($countries != '') {
            $blockedCountriesPieces = explode(",", $countries);
            foreach ($blockedCountriesPieces as $code) {
                $result .= ' client.geo.country_code == "' . $code . '" ||';
            }
        }

        return $result;
    }
}
