<?php declare(strict_types=1);

namespace BlockPlus\Form;

use Common\Form\Element as CommonElement;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;

/**
 * Fieldset of a block that displays the last messages of a social network.
 */
abstract class AbstractSocialFieldset extends Fieldset
{
    /**
     * Prefix of the ids of the elements.
     */
    protected $network = '';

    protected $accountInfo = '';

    protected $accountPlaceholder = '';

    /**
     * Key and label of the option to display the reposts.
     */
    protected $repostKey = 'reposts';

    protected $repostLabel = 'Display reposts'; // @translate

    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][account]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Account', // @translate
                    'info' => $this->accountInfo,
                ],
                'attributes' => [
                    'id' => $this->network . '-account',
                    'required' => true,
                    'placeholder' => $this->accountPlaceholder,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][limit]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Number of messages', // @translate
                ],
                'attributes' => [
                    'id' => $this->network . '-limit',
                    'min' => '0',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][' . $this->repostKey . ']',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => $this->repostLabel,
                ],
                'attributes' => [
                    'id' => $this->network . '-' . $this->repostKey,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][replies]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Display replies', // @translate
                ],
                'attributes' => [
                    'id' => $this->network . '-replies',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][privacy]',
                'type' => CommonElement\Note::class,
                'options' => [
                    'text' => 'Privacy: the images of the messages are loaded by the browsers of the visitors from the servers of the network, that receive their ip address. So this block is not compliant with the GDPR without the consent of the visitors.', // @translate
                ],
                'attributes' => [
                    'id' => $this->network . '-privacy',
                ],
            ])
        ;
    }
}
