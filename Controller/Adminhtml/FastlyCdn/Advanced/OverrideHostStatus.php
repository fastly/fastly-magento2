<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class OverrideHostStatus extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var Api
     */
    private $api;

    public function __construct(
        Action\Context $context,
        Api $api,
        Http $request,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->api = $api;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $version = $this->request->getParam('active_version');
        if (!$version) {
            return $json->setData([
                'status'    => false,
                'msg'       => 'Something went wrong, please try again later'
            ]);
        }

        try {
            $result = $this->api->getOverrideHost($version);
        } catch (LocalizedException $e) {
            return $json->setData([
                    'status'    => false,
                    'msg'       => 'URL format is not correct. Correct format e.g. `<yourbucket>.s3.amazonaws.com`'
                ]);
        }

        if (!$result) {
            return $json->setData([
               'status'     => false,
               'msg'        => 'Something went wrong, please try again later'
            ]);
        }

        $override_host = 'general.default_host';
        $ttl = 'general.default_ttl';
        $status = $result->{$override_host} !== '' ? true : false;
        return $json->setData([
           'status'                  => true,
           'override_host_status'    => $status,
           'general_default_host'    => $result->{$override_host},
           'general_default_ttl'     => $result->{$ttl}
        ]);
    }
}
