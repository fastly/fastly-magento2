<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest;

use Fastly\Cdn\Model\ManifestFactory;
use Fastly\Cdn\Model\ResourceModel\Manifest as ManifestResource;
use Fastly\Cdn\Model\Manifest;
use Fastly\Cdn\Model\Modly\Manifest as Modly;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Class Create
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest
 */
class Create extends Action
{
    /**
     * @var ManifestFactory
     */
    private $manifestFactory;

    /**
     * @var Manifest
     */
    private $manifest;

    /**
     * @var Modly
     */
    private $modly;

    /**
     * @var ManifestResource
     */
    private $manifestResource;

    /**
     * @var JsonFactory
     */
    private $resultJson;

    public function __construct(
        Context $context,
        ManifestFactory $manifestFactory,
        ManifestResource $manifestResource,
        Manifest $manifest,
        Modly $modly,
        JsonFactory $resultJsonFactory
    ) {
        $this->manifestFactory = $manifestFactory;
        $this->manifestResource = $manifestResource;
        $this->manifest = $manifest;
        $this->modly = $modly;
        $this->resultJson = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $manifests = $this->modly->getAllRepoManifests();
            $manifest = $this->manifestFactory->create();

            foreach ($manifests as $key => $value) {
                $id = $value['id'];
                $version = $value['version'];
                $name = $value['name'];
                $description = $value['description'];
                $content = json_encode($value);
                if (array_key_exists('properties', $value)) {
                    $properties = json_encode($value['properties']);
                } else {
                    $properties = '';
                }

                $vcl = json_encode($value['vcl']);

                $manifest->setManifestId($id);
                $manifest->setManifestVersion($version);
                $manifest->setManifestName($name);
                $manifest->setManifestDescription($description);
                $manifest->setManifestContent($content);
                $manifest->setManifestProperties($properties);
                $manifest->setManifestVcl($vcl);
                $this->saveManifest($manifest);
                $manifest->unsetData();
            }

            return $result->setData([
                'status' => true
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'status'    => false,
                'msg'       => $e->getMessage()
            ]);
        }
    }

    /**
     * @param $manifest
     * @throws \Exception
     */
    private function saveManifest($manifest)
    {
        $this->manifestResource->save($manifest);
    }
}
