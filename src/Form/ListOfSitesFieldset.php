<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;

class ListOfSitesFieldset extends Fieldset
{
    public function init(): void
    {
        // Prepare the list of sites to exclude.
        // No trigger to simplify process (it's already an extended class).
        $exclude = [
            'main' => 'Main site', // @translate
            'current' => 'Current site', // @translate
        ];
        if (class_exists('Internationalisation\Form\LanguageSwitcherFieldset', false)) {
            $exclude['translated'] = 'Translated sites'; // @translate
        }

        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][sort]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Sort', // @translate
                    'value_options' => [
                        'alpha' => 'Alphabetical', // @translate
                        'oldest' => 'Oldest first', // @translate
                        'newest' => 'Newest first', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'list-of-sites-sort',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][limit]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Max number of sites', // @translate
                    'info' => 'An empty value means no limit.', // @translate
                ],
                'attributes' => [
                    'id' => 'list-of-sites-limit',
                    'placeholder' => 'Unlimited', // @translate
                    'min' => 0,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][exclude]',
                'type' => Element\MultiCheckbox::class,
                'options' => [
                    'label' => 'Exclude sites', // @translate
                    'value_options' => $exclude,
                ],
                'attributes' => [
                    'id' => 'exclude',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][pagination]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Pagination', // @translate
                    'info' => 'Show pagination (only if a limit is set)', // @translate
                ],
                'attributes' => [
                    'id' => 'list-of-sites-pagination',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][summaries]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show summaries', // @translate
                ],
                'attributes' => [
                    'id' => 'list-of-sites-summaries',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][thumbnails]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Show thumbnails', // @translate
                ],
                'attributes' => [
                    'id' => 'list-of-sites-thumbnails',
                ],
            ])
        ;
    }
}
