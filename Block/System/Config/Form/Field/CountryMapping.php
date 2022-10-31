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

namespace Fastly\Cdn\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;

/**
 * Class for CountryMapping
 *
 */
class CountryMapping extends AbstractFieldArray
{
    /**
     * @var Factory
     */
    private $elementFactory;

    /**
     * @param Context $context
     * @param Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array   $data = []
    ) {
        $this->elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->addColumn('country_id', ['label' => __('Country Code')]);
        $this->addColumn('origin_website_id', ['label' => __('Origin Website')]);
        $this->addColumn('store_id', ['label' => __('Target Store View')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        $this->_template = 'Fastly_Cdn::system/config/form/field/array.phtml';

        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return mixed|string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (isset($this->_columns[$columnName])) {
            switch ($columnName) {
                case 'store_id':
                    $options = $this->getOptions(__('-- Select Store --'));
                    $element = $this->elementFactory->create('select');
                    $element->setForm(
                        $this->getForm()
                    )->setName(
                        $this->_getCellInputElementName($columnName)
                    )->setHtmlId(
                        $this->_getCellInputElementId('<%- _id %>', $columnName)
                    )->setValues(
                        $options
                    );
                    return str_replace("\n", '', $element->getElementHtml());
                case 'origin_website_id':
                    $options = $this->getOriginWebsiteOptions();
                    $element = $this->elementFactory->create('select');
                    $element->setForm(
                        $this->getForm()
                    )->setName(
                        $this->_getCellInputElementName($columnName)
                    )->setHtmlId(
                        $this->_getCellInputElementId('<%- _id %>', $columnName)
                    )->setValues(
                        $options
                    );
                    return str_replace("\n", '', $element->getElementHtml());
            }
        }

        return parent::renderCellTemplate($columnName);
    }

    /**
     * Get list of store views.
     *
     * @param string $label
     * @return array
     */
    protected function getOptions(string $label = ''): array
    {
        $options = [];
        foreach ($this->_storeManager->getWebsites() as $website) {
            $websiteOptions = [
                'label' => $website->getName() . " [" . $website->getCode() . "]",
                'value' => [],
            ];
            foreach ($website->getStores() as $store) {
                $websiteOptions['value'][] = [
                    'value' => $store->getId(),
                    'label' => $store->getName() . " [" . $store->getCode() . "]"
                ];

            }
            $options[] = $websiteOptions;
        }
        if ($label) {
            array_unshift($options, [
                'value' => '',
                'label' => $label
            ]);
        }
        return $options;
    }

    /**
     * Get Origin website options
     *
     * @return array[]
     */
    protected function getOriginWebsiteOptions(): array
    {
        $options = [
            [
                'label' => __('Any'),
                'value' => '',
            ],
        ];

        foreach ($this->_storeManager->getWebsites() as $website) {
            $options[] = [
                'label' => sprintf('%s [%s]', $website->getName(), $website->getCode()),
                'value' => $website->getId(),
            ];
        }

        return $options;
    }
}
