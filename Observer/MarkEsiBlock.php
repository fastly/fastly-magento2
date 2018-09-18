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
namespace Fastly\Cdn\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResponseInterface as Response;
use Fastly\Cdn\Model\Config as FastlyConfig;

/**
 * Class MarkEsiBlock
 *
 * @package Fastly\Cdn\Observer
 */
class MarkEsiBlock implements ObserverInterface
{
    /**
     * @var FastlyConfig
     */
    private $fastlyConfig;
    /**
     * @var Response
     */
    private $response;

    /**
     * MarkEsiPage constructor.
     * @param Response $response
     * @param FastlyConfig $fastlyConfig
     */
    public function __construct(
        Response $response,
        FastlyConfig $fastlyConfig
    ) {
        $this->fastlyConfig = $fastlyConfig;
        $this->response = $response;
    }

    /**
     * Set x-esi header on ESI response request
     * If omitted, causes issues with embedded esi tags
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer) // @codingStandardsIgnoreLine - required, but not needed
    {
        if ($this->fastlyConfig->isFastlyEnabled() != true) {
            return;
        }

        // If not set, causes issues with embedded ESI
        $this->response->setHeader("x-esi", "1");
    }
}
