<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Common\Form\Element\TraitOptionalElement;
use Laminas\Form\Element\Select;

class PageModelSelect extends Select
{
    use TraitOptionalElement;

    /**
     * @var bool
     */
    protected $separatePagesAndBlocks = false;

    public function setOptions($options)
    {
        if (array_key_exists('separate_pages_and_blocks', $options)) {
            $this->setSeparatePagesAndBlocks($options['separate_pages_and_blocks']);
        }

        return parent::setOptions($options);
    }

    public function getValueOptions(): array
    {
        if (!$this->separatePagesAndBlocks) {
            $result = [];
            foreach ($this->valueOptions as $key => $value) {
                $val = $value;
                if (is_array($value) && !isset($value['label'])) {
                    $val['label'] = $value['o:label'] ?? '[no label]';
                }
                $result[$key] = $val;
            }
            return $result;
        }

        // Separate full page model and simple list of blocks.
        $models = [
            'page_models' => [
                'label' => 'Page models', // @translate
                'options' => [],
            ],
            'block_groups' => [
                'label' => 'Blocks groups', // @translate
                'options' => [],
            ],
        ];

        foreach ($this->valueOptions as $name => $pageModel) {
            $key = isset($pageModel['o:layout_data']) || isset($pageModel['layout_data']) || !empty($pageModel['is_page_template'])
                ? 'page_models'
                : 'block_groups';
            $models[$key]['options'][$name] = $pageModel['o:label'] ?? $pageModel['label'] ?? '[Untitled]';
        }
        if (empty($models['page_models']['options'])) {
            unset($models['page_models']);
        }
        if (empty($models['block_groups']['options'])) {
            unset($models['block_groups']);
        }

        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $models = $prependValueOptions + $models;
        }

        return $models;
    }

    public function setSeparatePagesAndBlocks($separatePagesAndBlocks): self
    {
        $this->separatePagesAndBlocks = (bool) $separatePagesAndBlocks;
        return $this;
    }

    public function getSeparatePagesAndBlocks(): bool
    {
        return $this->separatePagesAndBlocks;
    }
}
