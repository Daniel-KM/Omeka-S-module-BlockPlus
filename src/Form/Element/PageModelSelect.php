<?php declare(strict_types=1);

namespace BlockPlus\Form\Element;

use Common\Form\Element\TraitOptionalElement;
use Common\Form\Element\TraitPrependValuesOptions;
use Laminas\Form\Element\Select;

class PageModelSelect extends Select
{
    use TraitOptionalElement;
    use TraitPrependValuesOptions;
}
