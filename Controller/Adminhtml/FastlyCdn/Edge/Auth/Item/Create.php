<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Edge\Auth\Item;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Fastly\Cdn\Model\Config;
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;

class Create extends Action
{
    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Fastly\Cdn\Model\Api
     */
    private $api;

    /**
     * @var Vcl
     */
    private $vcl;

    /**
     * ForceTls constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Config $config
     * @param Api $api
     * @param Vcl $vcl
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Config $config,
        Api $api,
        Vcl $vcl
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->config = $config;
        $this->api = $api;
        $this->vcl = $vcl;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();

        try {
            $activeVersion = $this->getRequest()->getParam('active_version');

            $dictionary = $this->api->getAuthDictionary($activeVersion);

            if ((is_array($dictionary) && empty($dictionary)) || !isset($dictionary->id)) {
                return $result->setData([
                    'status'    => 'empty',
                    'msg'       => 'Authentication dictionary does not exist.'
                ]);
            }

            $user = $this->getRequest()->getParam('auth_user');
            $pass = $this->getRequest()->getParam('auth_pass');
            $key = base64_encode($user . ':' . $pass);
            $this->api->upsertDictionaryItem($dictionary->id, $key, true);

            return $result->setData(['status' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
