<?php

declare(strict_types=1);

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement;

use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Model\ApiParametersResolver;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class GetTlsDomains
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\TlsManagement
 */
class GetTlsDomains extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var ApiParametersResolver
     */
    private $domainParametersResolver;

    /**
     * GetTlsDomains constructor.
     * @param Action\Context $context
     * @param JsonFactory $jsonFactory
     * @param ApiParametersResolver $domainParametersResolver
     * @param Api $api
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        ApiParametersResolver $domainParametersResolver,
        Api $api
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->api = $api;
        $this->domainParametersResolver = $domainParametersResolver;
    }
    public function execute()
    {
        $json = $this->jsonFactory->create();
        try {
            $result = $this->api->getTlsDomains();
        } catch (LocalizedException $e) {
            return $json->setData([
                'status' => false,
                'msg'    => $e->getMessage()
            ]);
        }

        if (!$result) {
            return $json->setData([
                'status' => true,
                'flag'   => false,
                'msg'    => '<p>You are not authorized.' .
                            '<a target="_blank"' .
                            'href="https://docs.fastly.com/en/guides/configuring-user-roles-and-permissions' .
                            '#changing-user-roles-and-access-permissions-for-existing-users">' .
                            'Follow the link for more information about permissions.</a></p>'
            ]);
        }

        $this->domainParametersResolver->combineDataAndIncludedDomains($result);

        return $json->setData([
            'status' => true,
            'flag'  => true,
            'domains'    => $result->data ?: [],
            'meta'  => $result->meta
        ]);
    }
}
