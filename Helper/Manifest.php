<?php

namespace Fastly\Cdn\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fastly\Cdn\Model\ManifestFactory;
use Fastly\Cdn\Model\ResourceModel\Manifest as ManifestResource;
use Fastly\Cdn\Model\ResourceModel\Manifest\Collection;
use Fastly\Cdn\Model\ResourceModel\Manifest\CollectionFactory;

/**
 * Class Manifest
 *
 * @package Fastly\Cdn\Helper
 */
class Manifest extends AbstractHelper
{
    /**
     * @var ManifestFactory
     */
    private $manifestFactory;

    /**
     * @var ManifestResource
     */
    private $manifestResource;

    /**
     * @var Collection
     */
    private $collection;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Manifest constructor.
     *
     * @param Context $context
     * @param ManifestFactory $manifestFactory
     * @param ManifestResource $manifestResource
     * @param Collection $collection
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Context $context,
        ManifestFactory $manifestFactory,
        ManifestResource $manifestResource,
        Collection $collection,
        Collectionfactory $collectionFactory
    ) {
        $this->manifestFactory = $manifestFactory;
        $this->manifestResource = $manifestResource;
        $this->collection = $collection;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    /**
     * Retrieves manifests from the database
     *
     * @return array
     */
    public function getManifests()
    {
        $manifestCollection = $this->collectionFactory->create()->getData();
        $manifests = [];
        foreach ($manifestCollection as $manifest) {
            $manifests[] = json_decode($manifest['manifest_content'], true);
        }
        return $manifests;
    }
}
