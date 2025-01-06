<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Common\Form\Element\TraitOptionalElement;
use Laminas\Form\Element\Select;

class BlockShowTitleSelect extends Select
{
    use TraitOptionalElement;

    protected $label = 'Show attachment title'; // @translate

    protected $valueOptions = [
        'item_title' => 'item title', // @translate
        'file_name' => 'media title', // @translate
        'no_title' => 'no title', // @translate
    ];
}
