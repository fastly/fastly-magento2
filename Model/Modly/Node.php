<?php

namespace Fastly\Cdn\Model\Modly;

class Node
{
    const BASE_CONFIG_PATH = 'system/full_page_cache/fastly_edge_modules';

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var array child nodes
     */
    private $children = [];

    /**
     * Node constructor.
     *
     * @param string $id
     * @param string $label
     * @param string $comment
     */
    public function __construct(
        $id,
        $label,
        $comment
    ) {
        $this->id       = $id;
        $this->label    = $label;
        $this->comment  = $comment;
    }

    /**
     * Returns identifier
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns Modly node config
     *
     * @return array
     */
    public function render()
    {
        return [
            'id'            => $this->getId(),
            'translate'     => 'label comment',
            'showInDefault' => 1,
            'showInWebsite' => 0,
            'showInStore'   => 0,
            'sortOrder'     => 1,
            'label'         => $this->label,
            'comment'       => $this->comment,
            '_elementType'  => 'group',
            'path'          => self::BASE_CONFIG_PATH,
            'children'      => $this->children
        ];
    }

    /**
     * Insert text input for Modly config
     *
     * @param $id
     * @param $label
     * @param $comment
     */
    public function addTextInput($id, $label, $comment, $required = true)
    {
        $this->children[$id] = [
            'id'            => $id,
            'type'          => 'text',
            'translate'     => 'label comment',
            'showInDefault' => 1,
            'showInWebsite' => 0,
            'showInStore'   => 0,
            'sortOrder'     => count($this->children),
            'label'         => $label,
            'comment'       => $comment,
            'validate'      => ($required == true) ? 'required-entry' : '',
            '_elementType'  => 'field',
            'path'          => self::BASE_CONFIG_PATH . '/' . $this->id
        ];
    }

    public function addOptionInput($id, $label, $comment, $optionsData, $required = true)
    {
        $options['option'] = [];
        foreach ($optionsData as $optionValue => $optionLabel) {
            $options['option'][] = [
                'name' => strtolower(str_replace(' ', '_', trim($optionValue))),
                'value' => $optionValue,
                'label' => $optionLabel
            ];
        }

        $this->children[$id] = [
            'id'            => $id,
            'type'          => 'select',
            'translate'     => 'label comment',
            'showInDefault' => 1,
            'showInWebsite' => 0,
            'showInStore'   => 0,
            'sortOrder'     => count($this->children),
            'label'         => $label,
            'comment'       => $comment,
            'validate'      => ($required == true) ? 'required-entry' : '',
            '_elementType'  => 'field',
            'options'       => $options,
            'path'          => self::BASE_CONFIG_PATH . '/' . $this->id
        ];
    }
}
