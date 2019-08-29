<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced;

use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class OverrideHostSwitcher extends Action
{
    /**
     * @var Http
     */
    private $request;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Vcl
     */
    private $vcl;

    public function __construct(
        Action\Context $context,
        Http $request,
        Api $api,
        Vcl $vcl,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->api = $api;
        $this->jsonFactory = $jsonFactory;
        $this->vcl = $vcl;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $version = $this->request->getParam('active_version');
        if (!$version) {
            return $json->setData([
                'status'    => false,
                'msg'       => 'Something went wrong, please try again'
            ]);
        }

        $overrideHost = $this->request->getParam('override_host');
        $status = $this->request->getParam('status') === 'true' ? true : false;
        $ttl = $this->request->getParam('default_ttl');
        $params = [
            'general.default_host'  => $overrideHost,
            'general.default_ttl'   => $ttl
        ];
        try {
            if (!$status) {
                $result = $this->_enableOverrideHost($version, $params);
            } else {
                $result = $this->_disableOverrideHost($version, $ttl);
            }

            if ($result['status'] !== false) {
                $result = $this->_handleActiveVersion($version, $result);
                $service = $this->api->checkServiceDetails();
                $result['next_version'] = $this->vcl->getNextVersion($service->versions);
            }

            return $json->setData($result);
        } catch (LocalizedException $e) {
            return $json->setData([
               'status'     => false,
               'msg'        => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $version
     * @param array $params
     * @return array
     * @throws LocalizedException
     */
    private function _enableOverrideHost($version, $params = [])
    {
        if (!filter_var($params['general.default_host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return [
                'status'    => false,
                'msg'       => 'URL format is not correct. Correct format e.g. `<yourbucket>.s3.amazonaws.com`'
            ];
        }
        $version = $this->_getClonedVersionNumber($version);
        if (!$version) {
            return [
                'status'    => false,
                'msg'       => 'Something went wrong, please try again'
            ];
        }
        $result = $this->api->createOverrideHost($version, $params);
        if (!$result) {
            return [
                'status'    => false,
                'msg'       => 'Something went wrong, please try again or review valid domain format. e.g. '
                                . '<yourbucket>.s3.amazonaws.com'
            ];
        }

        return  [
           'status'     => true,
           'switcher'   => 'enabled',
           'version'    => $version,
           'override_host'  =>  $params['general.default_host'],
           'ttl'        => $params['general.default_ttl']
        ];
    }

    /**
     * @param $version
     * @param $ttl
     * @return array
     * @throws LocalizedException
     */
    private function _disableOverrideHost($version, $ttl)
    {
        $params = [
            'general.default_host'   => '',
            'general.default_ttl'   => $ttl
        ];
        $version = $this->_getClonedVersionNumber($version);
        if (!$version) {
            return [
                'status'    => false,
                'msg'       => 'Something went wrong, please try again'
            ];
        }

        $result = $this->api->createOverrideHost($version, $params);
        if (!$result) {
            return [
                'status'    => false,
                'msg'       => 'Something went wrong, please try again'
            ];
        }

        return  [
            'status'     => true,
            'switcher'   => 'disabled',
            'version'    => $version,
            'override_host'  =>  $params['general.default_host'],
            'ttl'        => $params['general.default_ttl']
        ];
    }

    /**
     * @param $version
     * @return int
     * @throws LocalizedException
     */
    private function _getClonedVersionNumber($version)
    {
        $clone = $this->api->cloneVersion($version);
        if (!$clone) {
            return 0;
        }

        return $clone->number;
    }

    /**
     * @param array $params
     * @return array
     * @throws LocalizedException
     */
    private function _handleActiveVersion($ajaxedVersion, $params = [])
    {
        $activate = $this->request->getParam('activate') === 'true' ? true : false;
        if ($activate) {
            $this->api->activateVersion($params['version']);
            return $params;
        }
        $params['version']  = $ajaxedVersion;
        return $params;
    }
}
