<?php

namespace Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest;

use Fastly\Cdn\Model\ManifestFactory;
use Fastly\Cdn\Model\ResourceModel\Manifest as ManifestResource;
use Fastly\Cdn\Model\Manifest;
use Fastly\Cdn\Model\Modly\Manifest as Modly;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Fastly\Cdn\Model\ResourceModel\Manifest\CollectionFactory;

/**
 * Class Create
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest
 */
class ToggleModules extends Action
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

    private $request;

    private $collectionFactory;

    public function __construct(
        Context $context,
        ManifestFactory $manifestFactory,
        ManifestResource $manifestResource,
        Manifest $manifest,
        Modly $modly,
        JsonFactory $resultJsonFactory,
        Http $request,
        CollectionFactory $collectionFactory
    ) {
        $this->manifestFactory = $manifestFactory;
        $this->manifestResource = $manifestResource;
        $this->manifest = $manifest;
        $this->modly = $modly;
        $this->resultJson = $resultJsonFactory;
        $this->request = $request;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $checkedModules = $this->getRequest()->getParam('checked_modules');
            if ($checkedModules == null) {
                $checkedModules = [];
            }
            $moduleCollection = $this->collectionFactory->create()->getData();

            foreach ($moduleCollection as $module) {
                $manifest = $this->manifestFactory->create();
                $moduleId = $module['manifest_id'];
                if (in_array($moduleId, $checkedModules)) {
                    $manifest->setManifestId($moduleId);
                    $manifest->setManifestStatus(1);
                } else {
                    $manifest->setManifestId($moduleId);
                    $manifest->setManifestStatus(0);
                }
                $this->saveManifest($manifest);
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
