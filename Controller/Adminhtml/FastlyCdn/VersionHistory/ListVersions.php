<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\VersionHistory;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class ListVersions extends Action
{

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct
    (
        Action\Context $context,
        JsonFactory $jsonFactory
    )
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $json = $this->jsonFactory->create();
        return;
    }
}
