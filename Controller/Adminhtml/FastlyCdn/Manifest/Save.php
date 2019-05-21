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
 * Class Save
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

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJson->create();
        $isValid = '';
        try {
            $fieldData = $this->getRequest()->getParam('field_data');
            $moduleId = $this->getRequest()->getParam('module_id');
            $groupName = $this->getRequest()->getParam('group_name');
            $moduleData = $this->modly->getModule($moduleId);
            $moduleProperties = json_decode($moduleData->getManifestProperties());

            if ($fieldData && $groupName == '') {
                $isValid = $this->processSimple($fieldData[0], $moduleProperties);
            } elseif ($fieldData && $groupName != '') {
                $isValid = $this->processGroup($fieldData[$groupName], $moduleProperties);
            }

            if (!empty($isValid)) {
                throw new LocalizedException(__($isValid));
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
     * @param $fieldData
     * @param $moduleProperties
     * @return string
     */
    private function processSimple($fieldData, $moduleProperties)
    {
        $isValid = '';
        foreach ($fieldData as $key => $value) {
            foreach ($moduleProperties as $properties) {
                $name = $this->getName($properties);

                if (isset($name) && $key == $name) {
                    $type = $this->getType($properties);
                    $label = $this->getLabel($properties);
                    $validation = $this->getValidation($properties);
                    $required = $this->getRequired($properties);

                    $isValid = $this->validateField($type, $validation, $value, $label, $required);
                }
            }
        }

        return $isValid;
    }

    /**
     * @param $fieldData
     * @param $moduleProperties
     * @return string
     */
    private function processGroup($fieldData, $moduleProperties)
    {
        $isValid = '';
        foreach ($fieldData[0] as $index => $value) {
            foreach ($moduleProperties as $properties) {
                $groupProperties = $this->getGroupProperties($properties);
                if ($groupProperties) {
                    foreach ($groupProperties as $props) {
                        $name = $this->getName($props);

                        if (isset($name) && $index == $name) {
                            $type = $this->getType($props);
                            $label = $this->getLabel($props);
                            $validation = $this->getValidation($props);
                            $required = $this->getRequired($props);

                            $isValid = $this->validateField($type, $validation, $value, $label, $required);
                        }
                    }
                }
            }
        }

        return $isValid;
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
     * @param $type
     * @param $validation
     * @param $value
     * @param $label
     * @param $required
     * @return string
     */
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

    /**
     * @param $properties
     * @return null
     */
    private function getName($properties)
    {
        if (property_exists($properties, 'name')) {
            return $properties->name;
        } else {
            return null;
        }
    }

    /**
     * @param $properties
     * @return null
     */
    private function getType($properties)
    {
        if (property_exists($properties, 'type')) {
            return $properties->type;
        } else {
            return null;
        }
    }

    /**
     * @param $properties
     * @return null
     */
    private function getRequired($properties)
    {
        if (property_exists($properties, 'required')) {
            return $properties->required;
        } else {
            return null;
        }
    }

    /**
     * @param $properties
     * @return null|string
     */
    private function getValidation($properties)
    {
        if (property_exists($properties, 'validation')) {
            return '/^' . $properties->validation . '/';
        } else {
            return null;
        }
    }

    /**
     * @param $properties
     * @return null
     */
    private function getLabel($properties)
    {
        if (property_exists($properties, 'label')) {
            return $properties->label;
        } else {
            return null;
        }
    }

    /**
     * @param $properties
     * @return null
     */
    private function getGroupProperties($properties)
    {
        if (property_exists($properties, 'properties')) {
            return $properties->properties;
        } else {
            return null;
        }
    }
}
