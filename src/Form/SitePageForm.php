<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;

class SitePageForm extends \Omeka\Form\SitePageForm
{
    /**
     * @var array
     */
    protected $pageModels;

    public function init()
    {
        parent::init();

        if ($this->getOption('addPage') && $this->pageModels) {
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
            foreach ($this->pageModels as $name => $pageModel) {
                $key = isset($pageModel['o:layout_data']) || isset($pageModel['layout_data'])
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

            $this
                ->add([
                    'name' => 'page_model',
                    'type' => CommonElement\OptionalSelect::class,
                    'options' => [
                        'label' => 'Page models and blocks groups', // @translate
                        'empty_option' => 'Default', // @translate
                        'value_options' => $models,
                    ],
                    'attributes' => [
                        'id' => 'page-model',
                    ],
                ]);
        }
    }

    public function setPageModels(?array $pageModels): self
    {
        $this->pageModels = $pageModels;
        return $this;
    }
}
