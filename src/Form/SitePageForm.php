<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;

class SitePageForm extends \Omeka\Form\SitePageForm
{
    /**
     * @var array
     */
    protected $pageTemplates;

    public function init()
    {
        parent::init();

        if ($this->getOption('addPage')) {
            if ($this->pageTemplates) {
                $this
                    ->add([
                        'name' => 'o:layout_data[template_name]',
                        'type' => CommonElement\OptionalSelect::class,
                        'options' => [
                            'label' => 'Page template', // @translate
                            'empty_option' => 'Default', // @translate
                            'value_options' => $this->pageTemplates,
                        ],
                        'attributes' => [
                            'id' => 'template-name',
                        ],
                    ]);
            }
        }
    }

    public function setPageTemplates(?array $pageTemplates): self
    {
        $this->pageTemplates = $pageTemplates;
        return $this;
    }
}
