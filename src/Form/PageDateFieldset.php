<?php declare(strict_types=1);

namespace BlockPlus\Form;

use BlockPlus\Form\Element as BlockPlusElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\View\Helper\I18n;

class PageDateFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][dates]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Dates', // @translate
                    'value_options' => [
                        'created' => 'Created', // @translate
                        'modified' => 'Updated', // @translate
                        'created_and_modified' => 'Created and updated', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'page-date-dates',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][format_date]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Format date', // @translate
                    'value_options' => [
                        I18n::DATE_FORMAT_NONE => 'None', // @translate
                        I18n::DATE_FORMAT_SHORT => 'Short', // @translate
                        I18n::DATE_FORMAT_MEDIUM => 'Medium', // @translate
                        I18n::DATE_FORMAT_LONG => 'Long', // @translate
                        I18n::DATE_FORMAT_FULL => 'Full', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'page-date-format-date',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][format_time]',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Format time', // @translate
                    'value_options' => [
                        I18n::DATE_FORMAT_NONE => 'None', // @translate
                        I18n::DATE_FORMAT_SHORT => 'Short', // @translate
                        I18n::DATE_FORMAT_MEDIUM => 'Medium', // @translate
                        I18n::DATE_FORMAT_LONG => 'Long', // @translate
                        I18n::DATE_FORMAT_FULL => 'Full', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'page-date-format-time',
                ],
            ])
        ;
    }
}
