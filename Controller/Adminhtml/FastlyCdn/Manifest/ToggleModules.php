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
use Fastly\Cdn\Model\Api;
use Fastly\Cdn\Helper\Vcl;
use Fastly\Cdn\Model\Config;
use Magento\Framework\Exception\LocalizedException;

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
    /**
     * @var Http
     */
    private $request;
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Api
     */
    private $api;

    private $vcl;

    private $enabledModules = [];

    public function __construct(
        Context $context,
        ManifestFactory $manifestFactory,
        ManifestResource $manifestResource,
        Manifest $manifest,
        Modly $modly,
        JsonFactory $resultJsonFactory,
        Http $request,
        CollectionFactory $collectionFactory,
        Api $api,
        Vcl $vcl
    ) {
        $this->manifestFactory = $manifestFactory;
        $this->manifestResource = $manifestResource;
        $this->manifest = $manifest;
        $this->modly = $modly;
        $this->resultJson = $resultJsonFactory;
        $this->request = $request;
        $this->collectionFactory = $collectionFactory;
        $this->api = $api;
        $this->vcl = $vcl;
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
                    if ($module['manifest_status'] == 1) {
                        $this->enabledModules[$moduleId] = $module['manifest_vcl'];
                    }
                    $manifest->setManifestId($moduleId);
                    $manifest->setManifestStatus(0);
                    $manifest->setLastUploaded(null);
                }
                $this->saveManifest($manifest);
            }

            if ($this->enabledModules) {
                $removeStatus = $this->removeManifests($this->enabledModules);
                if ($removeStatus != false) {
                    throw new LocalizedException($removeStatus);
                }
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

    /**
     * @param $enabledModules
     * @return bool|\Exception
     */
    private function removeManifests($enabledModules)
    {
        try {
            $service = $this->api->checkServiceDetails();
            $activeVersion = $this->vcl->getCurrentVersion($service->versions);
            $existingSnippets = [];

            foreach ($enabledModules as $key => $value) {
                $moduleVcl = json_decode($value);
                foreach ($moduleVcl as $vcl) {
                    $type = $vcl->type;
                    $reqName = Config::FASTLY_MODLY_MODULE . '_' . $key . '_' . $type;
                    $checkIfSnippetExist = $this->api->hasSnippet($activeVersion, $reqName);
                    if ($checkIfSnippetExist) {
                        $existingSnippets[] = $reqName;
                    }
                }
            }

            if ($existingSnippets) {
                $clone = $this->api->cloneVersion($activeVersion);
                foreach ($existingSnippets as $snippet) {
                    $this->api->removeSnippet($clone->number, $snippet);
                }
                $this->api->validateServiceVersion($clone->number);
                $this->api->activateVersion($clone->number);

                $comment = ['comment' => 'Magento Module deleted Fastly Edge Module snippets.'];
                $this->api->addComment($clone->number, $comment);
            }
            return false;
        } catch (\Exception $e) {
            return $e;
        }
    }
}
