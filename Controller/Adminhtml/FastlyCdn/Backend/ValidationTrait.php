<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Backend;

use Magento\Framework\Exception\LocalizedException;

trait ValidationTrait
{
    /**
     * @param $param
     * @return mixed|null
     */
    private function processRequest($param)
    {
        $request = $this->getRequest()->getParam($param);
        if ($request == '') {
            return null;
        }
        return $request;
    }

    /**
     * @param float $maxTls
     * @param float $minTls
     * @throws LocalizedException
     */
    private function validateVersion($maxTls, $minTls)
    {
        if ($maxTls == 0) {
            return;
        } elseif ($maxTls < $minTls) {
            throw new LocalizedException(__("Maximum TLS version must be higher than the minimum TLS version."));
        }
    }

    /**
     * @param $name
     * @throws LocalizedException
     */
    private function validateName($name)
    {
        if (trim($name) == "") {
            throw new LocalizedException(__("Name can't be blank"));
        }
    }

    /**
     * @param $address
     * @throws LocalizedException
     */
    private function validateAddress($address)
    {
        if (!filter_var($address, FILTER_VALIDATE_IP) &&
            !filter_var($address, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new LocalizedException(__('Address ' . $address . ' is not a valid IPv4, IPv6 or hostname.'));
        }
    }

    /**
     * @param $override
     * @return string|null
     * @throws LocalizedException
     */
    private function validateOverride($override)
    {
        if ($override === '') {
            return null;
        }

        if (!filter_var($override, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new LocalizedException(__('Override host ' . $override . ' is not a valid hostname.'));
        }

        return $override;
    }

    /**
     * @param $clone
     * @param $conditionName
     * @param $applyIf
     * @param $conditionPriority
     * @param $selCondition
     * @return mixed
     * @throws LocalizedException
     */
    private function createCondition($clone, $conditionName, $applyIf, $conditionPriority, $selCondition)
    {
        if ($conditionName == $selCondition && !empty($selCondition) &&
            !$this->api->getCondition($clone->number, $conditionName)) {
            $condition = [
                'name'      => $conditionName,
                'statement' => $applyIf,
                'type'      => 'REQUEST',
                'priority'  => $conditionPriority
            ];
            $createCondition = $this->api->createCondition($clone->number, $condition);
            return $createCondition->name;
        }
        return $selCondition;
    }
}
