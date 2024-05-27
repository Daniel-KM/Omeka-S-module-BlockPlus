<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class ShowcaseFieldset extends Fieldset
{
    public function init(): void
    {
        // Attachments fields are managed separately.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'showcase-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][html]',
                'type' => Element\Textarea::class,
                'attributes' => [
                    'id' => 'showcase-html',
                    'class' => 'block-html full wysiwyg',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][entries]',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Resource ids or pages',
                    'info' => 'Resource id, site, page, absolute or relative url',
                ],
                'attributes' => [
                    'id' => 'showcase-entries',
                    'rows' => 7,
                    'placeholder' => '1
item-set/2
main-site
/s/main-site/page/about
/s/other-site/item/3
asset/1
https://example.org = assetId = Title = Caption = Body
',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][thumbnail_type]',
                'type' => CommonElement\ThumbnailTypeSelect::class,
                'options' => [
                    'label' => 'Thumbnail type', // @translate
                ],
                'attributes' => [
                    'id' => 'showcase-thumbnail-type',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][show_title_option]',
                'type' => BlockPlusElement\BlockShowTitleSelect::class,
                'attributes' => [
                    'id' => 'showcase-show-title-option',
                    'class' => 'chosen-select',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][template]',
                'type' => BlockPlusElement\TemplateSelect::class,
                'options' => [
                    'label' => 'Template to display', // @translate
                    'info' => 'Templates are in folder "common/block-layout" of the theme and should start with "showcase".', // @translate
                    'template' => 'common/block-layout/showcase',
                ],
                'attributes' => [
                    'id' => 'showcase-template',
                    'class' => 'chosen-select',
                ],
            ]);
    }
}
