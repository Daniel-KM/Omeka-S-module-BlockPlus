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
        ;
    }
}
