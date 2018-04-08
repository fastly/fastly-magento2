<?php

namespace Fastly\Cdn\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\EntitySpecificHandlesList;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResponseInterface as Response;
use Magento\PageCache\Model\Config;

class MarkEsiPage implements ObserverInterface
{
    /**
     * Application config object
     *
     * @var \Magento\PageCache\Model\Config
     */
    private $config;

    /**
     * Is varnish enabled flag
     *
     * @var bool
     */
    private $isVarnishEnabled;

    /**
     * @var EntitySpecificHandlesList
     */
    private $entitySpecificHandlesList;

    /**
     * @var Base64Json
     */
    private $base64jsonSerializer;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Response
     */
    private $response;

    /**
     * MarkEsiPage constructor.
     *
     * @param \Magento\PageCache\Model\Config $config
     * @param EntitySpecificHandlesList|null $entitySpecificHandlesList
     * @param Json|null $jsonSerializer
     * @param Base64Json|null $base64jsonSerializer
     * @param Response $response
     */
    public function __construct( // @codingStandardsIgnoreLine - required by parent class
        Config $config,
        Response $response,
        EntitySpecificHandlesList $entitySpecificHandlesList = null,
        Json $jsonSerializer = null,
        Base64Json $base64jsonSerializer = null
    ) {
        $this->config = $config;
        $this->entitySpecificHandlesList = $entitySpecificHandlesList
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(EntitySpecificHandlesList::class);
        $this->jsonSerializer = $jsonSerializer
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Json::class);
        $this->base64jsonSerializer = $base64jsonSerializer
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Base64Json::class);
        $this->response = $response;
    }

    /**
     * Is varnish cache engine enabled
     *
     * @return bool
     */
    private function isVarnishEnabled()
    {
        if ($this->isVarnishEnabled === null) {
            $this->isVarnishEnabled = ($this->config->getType() == \Magento\PageCache\Model\Config::VARNISH);
        }
        return $this->isVarnishEnabled;
    }

    /**
     * Set x-esi header if block contains ttl attribute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var \Magento\Framework\View\Layout $layout */
        $layout = $event->getLayout();
        $name = $event->getElementName();
        /** @var AbstractBlock $block */
        $block = $layout->getBlock($name);
        if ($block instanceof AbstractBlock) {
            $blockTtl = $block->getTtl();
            if (isset($blockTtl) && $this->isVarnishEnabled()) {
                // This page potentially has ESIs so as a first cut let's mark it as such
                $this->response->setHeader("x-esi", "1");
            }
        }
    }
}
