<?php

namespace Fastly\Cdn\Controller\Adminhtml\Dashboard;

use Magento\Backend\Controller\Adminhtml\Dashboard\AjaxBlock;

class Historic extends AjaxBlock
{
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock('Fastly\Cdn\Block\Dashboard\Tab\Stats\Historic')
            ->toHtml();
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($output);
    }
}
