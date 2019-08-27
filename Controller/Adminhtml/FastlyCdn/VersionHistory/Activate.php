<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Fastly\Cdn\Model\Api;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Activate
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory
 */
class Activate extends Action
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var Http
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    /**
     * @var TypeListInterface
     */
    private $typeList;

    /**
     * Activate constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param TypeListInterface $typeList
     * @param Api $api
     * @param Http $request
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        TypeListInterface $typeList,
        Api $api,
        Http $request
    ) {
        parent::__construct($context);
        $this->api = $api;
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->typeList = $typeList;
    }

    /**
     * get version id from GET param, aktivate specific version
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $version = (int)$this->request->getParam('version');
        $oldVersion = (int)$this->request->getParam('active_version');
        try {
            $answer = $this->api->activateVersion($version);
            if (!$answer) {
                return $result->setData([
                    'status' => false,
                    'msg' => 'There is no version #' . $version
                ]);
            }
            $this->typeList->cleanType('config');
            return $result->setData([
                'old_version' => $oldVersion,
                'version' => $answer->number,
                'comment' => $answer->comment,
                'updated_at' => $answer->updated_at,
                'status' => true
            ]);
        } catch (LocalizedException $exception) {
            return $result->setData([
                'status' => false,
                'msg' => $exception->getMessage()
            ]);
        }
    }
}
