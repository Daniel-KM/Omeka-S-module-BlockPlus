<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Zend\View\Renderer\PhpRenderer;

class Html extends \Omeka\Site\BlockLayout\Html
{
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $html = $block->dataValue('html', '');
        return strlen($html)
            ? $view->partial('common/block-layout/html', ['html' => $html])
            : '';
    }
}
