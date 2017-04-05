<?php

namespace Fastly\Cdn\Controller\Adminhtml\Dashboard;

use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Controller\Result\JsonFactory;

class Apply extends \Magento\Backend\App\Action
{
    /**
     * @var Http
     */
    protected $request;

    /**
     * @var JsonFactory
     */
    protected $resultJson;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        Http $request,
        JsonFactory $resultJsonFactory
    )
    {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();
        $from = $this->getRequest()->getParam('from');
        $to = $this->getRequest()->getParam('to');

        return $result->setData(array('status' => true, 'from' => $from));
    }
}