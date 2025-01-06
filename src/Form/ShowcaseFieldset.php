<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Common\Form\Element as CommonElement;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class ShowcaseFieldset extends Fieldset
{
    public function init(): void
    {
        // Attachments fields are managed separately.

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][entries]',
                'type' => OmekaElement\ArrayTextarea::class,
                'options' => [
                    'label' => 'Resource ids or pages',
                    'info' => 'Resource id, site, page, absolute or relative url',
                ],
                'attributes' => [
                    'id' => 'showcase-entries',
                    'rows' => 8,
                    'placeholder' => <<<'TEXT'
                    1
                    item-set/2
                    main-site
                    contact-us
                    /s/main-site/page/about
                    /s/other-site/item/3
                    asset/1
                    https://example.org = assetId = Title = Caption = Body
                    TEXT,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][layout]',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Layout', // @translate
                    'value_options' => [
                        '' => 'Vertical', // @translate
                        'horizontal' => 'Horizontal', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'showcase-layout',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][media_display]',
                'type' => CommonElement\OptionalRadio::class,
                'options' => [
                    'label' => 'Media display',, // @translate
                    'value_options' => [
                        '' => 'Embed media', // @translate
                        'thumbnail' => 'Thumbnail only', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'showcase-media-display',
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
        ;
    }
}
