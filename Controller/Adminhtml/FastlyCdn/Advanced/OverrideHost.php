<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Advanced;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class OverrideHost extends Action
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
        JsonFactory $jsonFactory,
        Api $api,
        Http $request
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->api = $api;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        $active_version = (int)$this->request->getParam('active_version');
        if (!$active_version) {
            return $json->setData([
                'status' => false,
                'msg'    => 'Something went wrong, please try again later'
            ]);
        }

        $host = $this->request->getParam('host_name');
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return $json->setData([
                'status'    => false,
                'msg'       => 'URL format is not correct. Correct format e.g. `<yourbucket>.s3.amazonaws.com`'
            ]);
        }

        $activate = ($this->request->getParam('active') === 'true') ? true : false;
        try {
            $clone = $this->api->cloneVersion($active_version);

            $params = [
            'general.default_host'  => $host,
            'service_id'    => $clone->service_id,
            'version'       => $clone->number
            ];
            $result = $this->api->createOverrideHost($clone->number, $params);
            if (!$result) {
                return $json->setData([
                'status'    => false,
                'msg'       => 'Something went wrong, please try again'
                ]);
            }

            if (!$activate) {
                return $result->setData([
                'status' => true,
                'version' => $result->version,
                'activated' => $activate,
                'override_host' => $host
                ]);
            }

            $this->api->activateVersion($clone->number);
            return $json->setData([
            'status'    => true,
            'version'   => $result->version,
            'activated' => $activate,
            'override_host' => $host
            ]);
        } catch (LocalizedException $e) {
            return $json->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
