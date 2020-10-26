<?php
namespace BlockPlus\Form;

use Omeka\Form\Element\Asset;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class PageMetadataFieldset extends Fieldset
{
    public function init()
    {
        $pageTypes = $this->getPageTypes();

        $hasPageTypes = count($pageTypes);
        if (!$hasPageTypes) {
            $pageTypes = [
                'Set types in the parameters of the site.', // @translate
            ];
        }

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][type]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Page type', // @translate
                    'value_options' => $pageTypes,
                    'empty_option' => '',
                ],
                'attributes' => [
                    'id' => 'page-metadata-type',
                    'required' => true,
                    'class' => 'chosen-select',
                    'data-placeholder' => 'Select the page type…', // @translate
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][credits]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Credits', // @translate
                ],
                'attributes' => [
                    'id' => 'page-metadata-credits',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][summary]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Summary', // @translate
                ],
                'attributes' => [
                    'id' => 'page-metadata-summary',
                    'class' => 'block-html full wysiwyg',
                    'rows' => 5,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][featured]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Featured', // @translate
                ],
                'attributes' => [
                    'id' => 'page-metadata-featured',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][tags]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Tags', // @translate
                    'infos' => 'Comma-separated list of keywords', // @translate
                ],
                'attributes' => [
                    'id' => 'page-metadata-tags',
                    'placeholder' => 'alpha, beta, gamma',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][params]',
                'type' => Element\Textarea::class,
                'options' => [
                    'label' => 'Params', // @translate
                    'info' => 'The params can be fetched as raw text, key/value pairs, or json.', // @translate
                ],
                'attributes' => [
                    'id' => 'page-metadata-params',
                    'rows' => 5,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][cover]',
                'type' => Asset::class,
                'options' => [
                    'label' => 'Cover image', // @translate
                ],
                'attributes' => [
                    'id' => 'page-metadata-cover',
                ],
            ])
        ;
    }

    public function setPageTypes(array $pageTypes)
    {
        $this->pageTypes = $pageTypes;
        return $this;
    }

    public function getPageTypes()
    {
        return $this->pageTypes;
    }
}
