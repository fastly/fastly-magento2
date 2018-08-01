<?php

namespace Fastly\Cdn\Plugin\Config;

use Fastly\Cdn\Model\Modly\Manifest;
use Fastly\Cdn\Model\Modly\NodeFactory;
use Fastly\Cdn\Helper\Manifest as ManifestData;
use Magento\Config\Model\Config\ScopeDefiner;
use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\Structure\Element\Section;
use Magento\Config\Model\Config\Structure\ElementInterface;

class StructurePlugin
{
    /**
     * @var ScopeDefiner
     */
    private $scopeDefiner;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    /**
     * @var Manifest
     */
    private $manifest;

    /**
     * @var bool
     */
    private $isLoaded = false;

    private $manifestData;

    /**
     * StructurePlugin constructor.
     *
     * @param ScopeDefiner $scopeDefiner
     * @param NodeFactory $nodeFactory
     * @param Manifest $manifest
     */
    public function __construct(
        ScopeDefiner $scopeDefiner,
        NodeFactory $nodeFactory,
        Manifest $manifest,
        ManifestData $manifestData
    ) {
        $this->scopeDefiner = $scopeDefiner;
        $this->nodeFactory  = $nodeFactory;
        $this->manifest     = $manifest;
        $this->manifestData = $manifestData;
    }

    /**
     * Injects modlyies to configuration
     *
     * @param \Closure $proceed
     * @param array $pathParts
     * @return ElementInterface|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetElementByPathParts(\Closure $proceed, array $pathParts)
    {
        /** @var Section $result */
        $result = $proceed($pathParts);

        if ($this->isLoaded == true || false) {
            return $result;
        }

        if (($result instanceof Section) == false) {
            return $result;
        }

        if (isset($pathParts[0]) == false || $pathParts[0] != 'system') {
            return $result;
        }

        $this->isLoaded = true;
        $data = $result->getData();

        if (isset($data['children']['full_page_cache']['children']['fastly_edge_modules']['children']) == false) {
            return $result;
        }

        $original = $data['children']['full_page_cache']['children']['fastly_edge_modules']['children'];
        $data['children']['full_page_cache']['children']['fastly_edge_modules']['children'] = array_merge(
            $original,
            $this->loadModlyData()
        );

        $result->setData(
            $data,
            $this->scopeDefiner->getScope()
        );

        return $result;
    }

    private function loadModlyData()
    {
        $sampleData = $this->manifest->getActiveModlyManifests();
        $result = [];

        foreach ($sampleData as $data) {
            $nodeData = json_decode($data['manifest_content']);
            /** @var \Fastly\Cdn\Model\Modly\Node $node */
            $node = $this->nodeFactory->create(
                [
                    'id'        => $nodeData['id'],
                    'label'     => $nodeData['name'],
                    'comment'   => (isset($nodeData['description']) == true)? $nodeData['description'] : ''
                ]
            );
            foreach ($nodeData['properties'] as $property) {
                if (isset($property['options'])) {
                    $node->addOptionInput(
                        $property['name'],
                        $property['label'],
                        (isset($property['description']) == true)? $property['description'] : '',
                        $property['options']
                    );
                } else {
                    $node->addTextInput(
                        $property['name'],
                        $property['label'],
                        (isset($property['description']) == true)? $property['description'] : ''
                    );
                }
            }

            $result[$node->getId()] = $node->render();
        }

        return $result;
    }
}
