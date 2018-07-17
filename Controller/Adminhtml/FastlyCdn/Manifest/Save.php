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
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Create
 *
 * @package Fastly\Cdn\Controller\Adminhtml\FastlyCdn\Manifest
 */
class Save extends Action
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
// TODO: reduce cyclomatic complexity
    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $fieldData = $this->getRequest()->getParam('field_data')[0];
            $moduleId = $this->getRequest()->getParam('module_id');
            $moduleData = $this->modly->getModule($moduleId);
            $moduleProperties = json_decode($moduleData->getManifestProperties());

            foreach ($fieldData as $key => $value) {
                foreach ($moduleProperties as $properties) {
                    if (property_exists($properties, 'name')) {
                        $name = $properties->name;
                    }
                    if (property_exists($properties, 'required')) {
                        $required = $properties->required;
                    }
                    if (property_exists($properties, 'type')) {
                        $type = $properties->type;
                    }
                    if (property_exists($properties, 'validation')) {
                        $validation = '/^' . $properties->validation . '/';
                    }

                    if (isset($name) && $key == $name) {
                        if (isset($required) && $required == true && empty($value)) {
                            throw new LocalizedException(__('Please fill out the required fields.'));
                        }
                        if (!empty($value)) {
                            if ($type == 'string' && isset($validation) && !preg_match($validation, $value)) {
                                throw new LocalizedException(
                                    __('The "%1" field value contains invalid characters.', $properties->label)
                                );
                            } elseif ($type == 'integer' && ctype_digit($value) == false) {
                                throw new LocalizedException(
                                    __('The "%1" field must contain a numeric value.', $properties->label)
                                );
                            } elseif ($type == 'float' && is_float($value) == false) {
                                throw new LocalizedException(
                                    __('The "%1" field must contain a float value.', $properties->label)
                                );
                            } elseif ($type == 'ip' && !filter_var($value, FILTER_VALIDATE_IP)) {
                                throw new LocalizedException(
                                    __('The "%1" field must contain a valid IP format.', $properties->label)
                                );
                            } elseif ($type == 'path' && !filter_var(
                                $value,
                                FILTER_VALIDATE_URL,
                                FILTER_FLAG_PATH_REQUIRED
                            )
                            ) {
                                throw new LocalizedException(
                                    __('The "%1" field must have a valid URL path format.', $properties->label)
                                );
                            } elseif ($type == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
                                throw new LocalizedException(
                                    __('The "%1" field value must be a valid URL format.', $properties->label)
                                );
                            }
                        }
                    }
                }
            }

            $manifest = $this->manifestFactory->create();
            $manifest->setManifestId($moduleId);
            $manifest->setManifestValues(json_encode($fieldData));

            $this->saveManifest($manifest);

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
