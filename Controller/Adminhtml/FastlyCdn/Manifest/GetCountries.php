<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\Config\Source\Country;
use Fastly\Cdn\Model\Modly\Manifest;

/**
 * Class GetCustomSnippets
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Vcl
 */
class GetCountries extends Action
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
     * @var Manifest
     */
    private $manifest;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    private $countryHelper;

    /**
     * GetCountries constructor.
     *
     * @param Context $context
     * @param Http $request
     * @param JsonFactory $resultJsonFactory
     * @param Manifest $manifest
     * @param CountryFactory $countryFactory
     */
    public function __construct(
        Context $context,
        Http $request,
        JsonFactory $resultJsonFactory,
        Manifest $manifest,
        CountryFactory $countryFactory,
        Country $countryHelper
    ) {
        $this->request = $request;
        $this->resultJson = $resultJsonFactory;
        $this->manifest = $manifest;
        $this->countryFactory = $countryFactory;
        $this->countryHelper = $countryHelper;
        parent::__construct($context);
    }

    /**
     * Gte a list of all modules
     *
     * @return $this|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $countries = $this->countryHelper->toOptionArray();
            if (!$countries) {
                return $result->setData([
                    'status'    => false,
                    'msg'       => 'Could not fetch list countries.'
                ]);
            }

            return $result->setData([
                'status'    => true,
                'countries'  => $countries
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }
}
