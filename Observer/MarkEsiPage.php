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
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResponseInterface as Response;
use Magento\PageCache\Model\Config;
use Fastly\Cdn\Model\Config as FastlyConfig;

/**
 * Class MarkEsiPage
 *
 * @package Fastly\Cdn\Observer
 */
class MarkEsiPage implements ObserverInterface
{
    /**
     * Application config object
     *
     * @var \Magento\PageCache\Model\Config
     */
    private $config;
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
     * @param Config $config
     * @param Response $response
     * @param FastlyConfig $fastlyConfig
     */
    public function __construct(
        Config $config,
        Response $response,
        FastlyConfig $fastlyConfig
    ) {
        $this->config = $config;
        $this->fastlyConfig = $fastlyConfig;
        $this->response = $response;
    }

    /**
     * Set x-esi header if block contains ttl attribute
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->fastlyConfig->isFastlyEnabled() != true) {
            return;
        }

        $event = $observer->getEvent();
        $name = $event->getElementName();

        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $event->getLayout();

        /** @var AbstractBlock $block */
        $block = $layout->getBlock($name);

        if ($block instanceof AbstractBlock) {
            $blockTtl = $block->getTtl();
            if (isset($blockTtl)) {
                // This page potentially has ESIs so as a first cut let's mark it as such
                $this->response->setHeader("x-esi", "1");
            }
        }
    }
}
