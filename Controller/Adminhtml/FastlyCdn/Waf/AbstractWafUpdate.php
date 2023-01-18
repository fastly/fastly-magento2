<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Waf;

use Magento\Backend\App\Action;

abstract class AbstractWafUpdate extends Action
{
    /**
     * @param string[] $acls
     * @return string
     */
    protected function prepareWafAllowlist(array $acls): string
    {
        $list = [];

        foreach ($acls as $acl) {
            $list[] = sprintf('req.http.Fastly-Client-Ip ~ %s', $acl);
        }

        return implode(' || ', $list);
    }
}
