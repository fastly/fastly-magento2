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

    public function execute()
    {
        $result = $this->resultJson->create();
        try {
            $fieldData = $this->getRequest()->getParam('field_data');
            $moduleId = $this->getRequest()->getParam('module_id');
            $moduleData = $this->modly->getModule($moduleId);
            $moduleProperties = json_decode($moduleData->getManifestProperties());

            if ($fieldData) {
                foreach ($fieldData as $index => $data) {
                    foreach ($data as $key => $value) {
                        foreach ($moduleProperties as $properties) {
                            $name = $this->getName($properties);

                            $groupProperties = $this->getGroupProperties($properties);
                            if ($groupProperties) {
                                foreach ($groupProperties as $props) {
                                    $name = $this->getName($props);

                                    if (isset($name) && $key == $name) {
                                        $type = $this->getType($props);
                                        $label = $this->getLabel($props);
                                        $validation = $this->getValidation($props);
                                        $required = $this->getRequired($props);

                                        $isValid = $this->validateField($type, $validation, $value, $label, $required);

                                        if (!empty($isValid)) {
                                            throw new LocalizedException(__($isValid));
                                        }
                                    }
                                }
                            }

                            if (isset($name) && $key == $name) {
                                $type = $this->getType($properties);
                                $label = $this->getLabel($properties);
                                $validation = $this->getValidation($properties);
                                $required = $this->getRequired($properties);

                                $isValid = $this->validateField($type, $validation, $value, $label, $required);

                                if (!empty($isValid)) {
                                    throw new LocalizedException(__($isValid));
                                }
                            }
                        }
                    }
                }

                $manifest = $this->manifestFactory->create();
                $manifest->setManifestId($moduleId);
                $manifest->setManifestValues(json_encode($fieldData));

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

    private function validateField($type, $validation, $value, $label, $required)
    {
        $message = '';
        if (!empty($required) && $required == true && empty($value)) {
            $message = 'Please fill out the required fields.';
        } elseif ($type == 'string' && !empty($validation) && !preg_match($validation, $value)) {
            $message = sprintf('The "%s" field value contains invalid characters.', $label);
        } elseif ($type == 'integer' && ctype_digit($value) == false) {
            $message = sprintf('The "%s" field must contain a numeric value.', $label);
        } elseif ($type == 'float' && is_float($value) == false) {
            $message = sprintf('The "%s" field must contain a float value.', $label);
        } elseif ($type == 'ip' && !filter_var($value, FILTER_VALIDATE_IP)) {
            $message = sprintf('The "%s" field must contain a valid IP format.', $label);
        } elseif ($type == 'path' && !filter_var(
            $value,
            FILTER_VALIDATE_URL,
            FILTER_FLAG_PATH_REQUIRED
        )
        ) {
            $message = sprintf('The "%s" field must have a valid URL path format.', $label);
        } elseif ($type == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            $message = sprintf('The "%s" field value must be a valid URL format.', $label);
        }
        return $message;
    }

    private function getName($properties)
    {
        if (property_exists($properties, 'name')) {
            return $properties->name;
        } else {
            return null;
        }
    }

    private function getType($properties)
    {
        if (property_exists($properties, 'type')) {
            return $properties->type;
        } else {
            return null;
        }
    }

    private function getRequired($properties)
    {
        if (property_exists($properties, 'required')) {
            return $properties->required;
        } else {
            return null;
        }
    }

    private function getValidation($properties)
    {
        if (property_exists($properties, 'validation')) {
            return '/^' . $properties->validation . '/';
        } else {
            return null;
        }
    }

    private function getLabel($properties)
    {
        if (property_exists($properties, 'label')) {
            return $properties->label;
        } else {
            return null;
        }
    }

    private function getGroupProperties($properties)
    {
        if (property_exists($properties, 'properties')) {
            return $properties->properties;
        } else {
            return null;
        }
    }
}
