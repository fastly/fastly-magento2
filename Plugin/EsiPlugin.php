<?php

namespace Fastly\Cdn\Plugin;

class EsiPlugin
{

    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\PageCache\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\PageCache\Model\Config $config
    ) {
        $this->response = $response;
        $this->config = $config;
    }

    public function afterGetOutput(\Magento\Framework\View\Layout $subject, $result)
    {
        if ($subject->isCacheable() && $this->config->isEnabled()) {
            $tags = [];
            foreach ($subject->getAllBlocks() as $block) {
                if ($block instanceof \Magento\Framework\DataObject\IdentityInterface) {
                    $isEsiBlock = $block->getTtl() > 0;
                    $isVarnish = $this->config->getType() == \Magento\PageCache\Model\Config::VARNISH;
                    if ($isVarnish && $isEsiBlock) {
                        continue;
                    }
                    $tags = array_merge($tags, $block->getIdentities());
                }
            }
            $tags = array_unique($tags);

            $fastlyTags = array(
                'catalog_product' => 'p',
                'catalog_category' => 'c',
                'cms_page' => 'cpg'
            );

            $headerTags = str_replace(array_keys($fastlyTags), $fastlyTags, implode(" ", $tags));

            $this->response->setHeader('X-Fastly-Tags', $headerTags);
        }
        return $result;
    }
}