<?php
/**
 * Fastly CDN for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Fastly CDN for Magento End User License Agreement
 * that is bundled with this package in the file LICENSE_FASTLY_CDN.txt.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Fastly CDN to newer
 * versions in the future. If you wish to customize this module for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Fastly
 * @package     Fastly_Cdn
 * @copyright   Copyright (c) 2016 Fastly, Inc. (http://www.fastly.com)
 * @license     BSD, see LICENSE_FASTLY_CDN.txt
 */
namespace Fastly\Cdn\Model;

use Fastly\Cdn\Model\ResourceModel\Manifest as ManifestResource;
use LightnCandy\LightnCandy;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Importer
 *
 * @package Fastly\Cdn\Model
 */
class Importer
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var \Fastly\Cdn\Model\Config
     */
    private $config;

    /**
     * @var ManifestFactory
     */
    private $manifestFactory;

    /**
     * @var ManifestResource
     */
    private $manifestResource;

    public function __construct(
        \Fastly\Cdn\Model\Api $api,
        \Fastly\Cdn\Model\Config $config,
        \Fastly\Cdn\Model\ManifestFactory $manifestFactory,
        ManifestResource $manifestResource
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->manifestFactory = $manifestFactory;
        $this->manifestResource = $manifestResource;
    }

    public function importEdgeAcls($version, $data)
    {
        $list = $this->api->getAcls($version);
        $currentAcls = array_map(function ($acl) { return $acl->name; }, $list);

        $newAcls = array_map(function ($aclName) use ($version, $currentAcls) {
            if (!in_array($aclName, $currentAcls)) {
                return $this->api->createAcl($version, ['name' => $aclName]);
            }
        }, array_keys((array) $data));
        $acls = array_filter(array_merge($list, $newAcls));

        foreach ($acls as $acl) {
            if (isset($data->{$acl->name}->items)) {
                foreach ($data->{$acl->name}->items as $i) {
                    $this->api->upsertAclItem($acl->id, $i->ip, $i->negated, $i->comment, $i->subnet);
                }
            }
        }
    }

    public function importEdgeDictionaries($version, $data)
    {
        $list = $this->api->getDictionaries($version);
        $currentDictionaries = array_map(function ($dictionary) { return $dictionary->name; }, $list);

        $newDictionaries = array_map(function ($dictionaryName) use ($version, $currentDictionaries) {
            if (!in_array($dictionaryName, $currentDictionaries)) {
                return $this->api->createDictionary($version, ['name' => $dictionaryName]);
            }
        }, array_keys((array) $data));
        $dictionaries = array_filter(array_merge($list, $newDictionaries));

        foreach ($dictionaries as $dictionary) {
            if (isset($data->{$dictionary->name}->items)) {
                foreach ($data->{$dictionary->name}->items as $i) {
                    $this->api->upsertDictionaryItem($dictionary->id, $i->item_key, $i->item_value);
                }
            }
        }
    }

    public function importActiveEdgeModules($version, $data)
    {
        $snippets = $this->buildSnippets($data);
        foreach ($data as $moduleId => $datum) {
            $fieldData = (array) $datum->manifest_values;
            $groupName = key((array) $fieldData);
            $this->saveActiveEdgeModule($moduleId, $fieldData, $groupName);
            $this->uploadActiveEdgeModule($version, $moduleId, $snippets[$moduleId]);
        }
    }

    protected function buildSnippets($data)
    {
        $snippets = [];
        foreach ($data as $moduleName => $moduleData) {
            $moduleVcls = $moduleData->manifest_content->vcl;
            $groupName = '';
            if (isset($moduleData->manifest_content->properties)) {
                foreach ($moduleData->manifest_content->properties as $property) {
                    if ($property->type === 'group') {
                        $groupName = $property->name;
                    }
                }
            }

            $templates = [];
            foreach ($moduleData->manifest_values as $fields) {
                foreach ($moduleVcls as $vcl) {
                    $context = ($groupName) ? $moduleData->manifest_values : $fields;
                    $php = LightnCandy::compile($vcl->template, $this->getLightncandyOptions());
                    $render = LightnCandy::prepare($php);
                    if ($render) {
                        $templates[] = [
                            'type' => $vcl->type,
                            'priority' => (isset($vcl->priority)) ? $vcl->priority : 45,
                            'snippet' => $render($context),
                        ];
                    }
                }
            }

            $snippets[$moduleName] = $templates;
        }
        return $snippets;
    }

    protected function getLightncandyOptions()
    {
        return [
            'flags' => LightnCandy::FLAG_HANDLEBARSJS_FULL,
            'helpers' => [
                'ifEq' => function ($a, $b, $o) {
                    return ($a === $b) ? $o['fn']() : $o['inverse']();
                },
                'replace' => function ($subject, $pattern, $replacement) {
                    return preg_replace($pattern, $replacement, $subject);
                },
                'ifMatch' => function ($subject, $pattern, $o) {
                    return (preg_match($pattern, $subject)) ? $o['fn']() : $o['inverse']();
                },
                'extract' => function ($subject, $pattern, $o) {
                    return $o['fn']();
                },
            ],
        ];
    }

    protected function saveActiveEdgeModule($moduleId, $fieldData, $groupName)
    {
        $this->validate($moduleId, $fieldData, $groupName);

        $manifest = $this->manifestFactory->create();
        $manifest->setManifestId($moduleId);
        $manifest->setManifestValues(json_encode($fieldData));
        $this->manifestResource->save($manifest);
    }

    protected function validate($moduleId, $fieldData, $groupName)
    {
        $moduleProperties = $this->getModuleProperties($moduleId);
        if ($fieldData && $groupName === '') {
            $errors = $this->validateSimple($fieldData[0], $moduleProperties);
        } elseif ($fieldData && $groupName != '') {
            $errors = $this->validateGroup($fieldData[$groupName], $moduleProperties);
        }
        if (!empty($errors)) {
            throw new LocalizedException(__(implode(', ', $errors)));
        }
    }

    protected function getModuleProperties($moduleId)
    {
        $modules = $this->config->getFastlyEdgeModules();
        if (!isset($modules[$moduleId])) {
            throw new LocalizedException(__('Unknown module: ' . $moduleId));
        }
        $moduleData = json_decode($modules[$moduleId]);
        return isset($moduleData->properties) ? $moduleData->properties : [];
    }

    protected function validateSimple($fieldData, $moduleProperties)
    {
        $errors = [];
        foreach ($fieldData as $key => $value) {
            foreach ($moduleProperties as $properties) {
                $value = $this->getObjectValue($properties, 'value');
                $errors[] = $this->validateField($properties, $key, $value);
            }
        }
        return array_filter($errors);
    }

    protected function validateGroup($fieldData, $moduleProperties)
    {
        $errors = [];
        foreach ($fieldData[0] as $index => $value) {
            foreach ($moduleProperties as $properties) {
                $groupProperties = $this->getObjectValue($properties, 'properties');
                if ($groupProperties) {
                    foreach ($groupProperties as $props) {
                        $errors[] = $this->validateField($props, $index, $value);
                    }
                }
            }
        }
        return array_filter($errors);
    }

    protected function validateField($properties, $key, $value)
    {
        $name = $this->getObjectValue($properties, 'name');
        if (!isset($name) || $key !== $name) {
            return false;
        }
        $type = $this->getObjectValue($properties, 'type');
        $validation = $this->getObjectValue($properties, 'validation');
        $label = $this->getObjectValue($properties, 'label');
        $required = $this->getObjectValue($properties, 'required');

        if (!empty($required) && $required == true && empty($value)) {
            return sprintf('%s: Please fill out the required fields.', $name);
        } elseif ($type == 'string' && !empty($validation) && !preg_match($validation, $value)) {
            return sprintf('%s: The "%s" field value contains invalid characters.', $name, $label);
        } elseif ($type == 'integer' && ctype_digit($value) == false) {
            return sprintf('%s: The "%s" field must contain a numeric value.', $name, $label);
        } elseif ($type == 'float' && is_float($value) == false) {
            return sprintf('%s: The "%s" field must contain a float value.', $name, $label);
        } elseif ($type == 'ip' && !filter_var($value, FILTER_VALIDATE_IP)) {
            return sprintf('%s: The "%s" field must contain a valid IP format.', $name, $label);
        } elseif ($type == 'path' && !filter_var($value,FILTER_VALIDATE_URL,FILTER_FLAG_PATH_REQUIRED)) {
            return sprintf('%s: The "%s" field must have a valid URL path format.', $name, $label);
        } elseif ($type == 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            return sprintf('%s: The "%s" field value must be a valid URL format.', $name, $label);
        }
        return false;
    }

    protected function getObjectValue($object, $key)
    {
        if (property_exists($object, $key)) {
            return $object->{$key};
        }
        return null;
    }

    protected function uploadActiveEdgeModule($version, $moduleId, $snippets)
    {
        foreach ($snippets as $key => $value) {
            $this->api->uploadSnippet($version, [
                'name'      => Config::FASTLY_MODLY_MODULE . '_' . $moduleId . '_' . $value['type'],
                'type'      => $value['type'],
                'dynamic'   => "0",
                'priority'  => $value['priority'],
                'content'   => $value['snippet']
            ]);
        }
    }
}
