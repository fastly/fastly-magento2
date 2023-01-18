<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Blocking;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

abstract class AbstractBlocking extends Action
{
    protected $configWriter;

    public function __construct(
        Context $context,
        ConfigWriter $configWriter
    ) {
        $this->configWriter = $configWriter;

        parent::__construct($context);
    }

    /**
     * @param string[] $countryCodes
     * @param string[] $acls
     * @param int $blockingType
     * @return string
     */
    protected function prepareBlockedItems(array $countryCodes, array $acls, int $blockingType): string
    {
        $list = [];
        foreach ($countryCodes as $countryCode) {
            $list[] = sprintf('client.geo.country_code == "%s"', $countryCode);
        }

        foreach ($acls as $acl) {
            $list[] = sprintf('req.http.Fastly-Client-Ip ~ %s', $acl);
        }

        $result = implode(' || ', $list);
        if ($blockingType === 1 && !empty($result)) {
            $result = sprintf('!(%s)', $result);
        }

        return $result;
    }

    protected function storeConfigArray(string $path, array $data): void
    {
        $this->configWriter->save(
            $path,
            implode(',', $data),
            'default',
            '0'
        );
    }

    protected function getParamArray(string $param): array
    {
        $request = $this->getRequest();

        $data = $request->getParam($param);
        if (empty($data)) {
            return [];
        }

        return array_map(static function ($row) {
            return $row['value'];
        }, $data);
    }
}
