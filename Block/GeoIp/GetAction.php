<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Block\GeoIp;

use Fastly\Cdn\Model\Config;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Framework\App\ResponseInterface as Response;
use Magento\Framework\UrlInterface as Url;
use Magento\Framework\Url\EncoderInterface;

/**
 * Class GetAction
 *
 * @package Fastly\Cdn\Block\GeoIp
 */
class GetAction extends AbstractBlock
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var Url
     */
    private $url;
    /**
     * @var EncoderInterface
     */
    private $urlEncoder;

    /**
     * GetAction constructor.
     * @param Config $config
     * @param Context $context
     * @param Response $response
     * @param Url $url
     * @param EncoderInterface $urlEncoder
     */
    public function __construct(
        Config $config,
        Context $context,
        Response $response,
        Url $url,
        EncoderInterface $urlEncoder,
        array $data = []
    ) {
        $this->config = $config;
        $this->response = $response;
        $this->url = $url;
        $this->urlEncoder = $urlEncoder;

        parent::__construct($context, $data);
    }

    /**
     * Renders ESI GeoIp block
     *
     * @return string
     */
    protected function _toHtml() // @codingStandardsIgnoreLine - required by parent class
    {
        if ($this->config->isGeoIpEnabled() == false || $this->config->isFastlyEnabled() == false) {
            return parent::_toHtml();
        }

        /** @var string $actionUrl */
        $actionUrl = $this->getUrl('fastlyCdn/geoip/getaction');
        $vclUploaded = $this->_request->getServer('HTTP_FASTLY_MAGENTO_VCL_UPLOADED');
        $currentUrl = $this->url->getCurrentUrl();
        $baseUrl = $this->url->getBaseUrl();
        $webTypeUrl = $this->url->getBaseUrl(['_type' => Url::URL_TYPE_WEB]);
        
        if (strpos($currentUrl, $baseUrl) !== false) {
            $targetUrl = $currentUrl;
        } else {
            $targetUrl = str_replace($webTypeUrl, $baseUrl, $currentUrl);
        }

        if ($vclUploaded) {
            $actionUrl = $actionUrl . '?uenc=' . $this->urlEncoder->encode($targetUrl);
        }

        // This page has an esi tag, set x-esi header if it is not already set
        $header = $this->response->getHeader('x-esi');
        if (empty($header)) {
            $this->response->setHeader("x-esi", "1");
        }
        // HTTPS ESIs are not supported so we need to turn them into HTTP
        return sprintf(
            '<esi:include src=\'%s\' />',
            preg_replace(
                "/^https/",
                "http",
                $actionUrl
            )
        );
    }
}
