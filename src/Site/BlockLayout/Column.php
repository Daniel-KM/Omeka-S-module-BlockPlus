<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Column extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Column'; // @translate
    }

    public function prepareRender(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/block-plus.css', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();

        $data['tag'] = $data['tag'] ?: 'div';
        $data['class'] = $data['type'] === 'end'
            ? ''
            // Stricter than w3c standard.
            : preg_replace('/[^A-Za-z0-9_ -]/', '', $data['class']);

        // TODO Find a way to check columns during hydration, with new blocks. May be possible at least for the last block of the page.
        $block->setData($data);

        if (!$block->getId()) {
            return;
        } else {
            return;
        }

        // Check and save the previous tag to close elements quickly.
        // Blocks are automatically ordered by position (see entity SitePage).
        $blocks = $block->getPage()->getBlocks();
        $columns = [];
        $tagStack = [];
        foreach ($blocks as $blk) {
            if ($blk->getLayout() !== 'column') {
                continue;
            }

            $dta = $blk->getData();
            $column = [
                'type' => $dta['type'],
                'tag' => $dta['tag'] ?: 'div',
                'class' => $dta['class'],
                'close' => null,
            ];
            switch ($dta['type']) {
                case 'end':
                case 'inter':
                    if (empty($tagStack)) {
                        $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'Type "intermediate" and "end" columns must be after a block "start" or "intermediate".'); // @translate
                        return;
                    }
                    $column['close'] = array_pop($tagStack);
                    if ($dta['type'] === 'end') {
                        break;
                    }
                    // no break.
                case 'start':
                    $tagStack[] = $dta['tag'];
                    break;
                default:
                    $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'Unauthorized type for block column.'); // @translate
                    return;
            }
            $columns[$blk->getPosition()] = $column;
        }

        if (count($columns) < 2) {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'A block "column" cannot be single.'); // @translate
            return;
        }

        ksort($columns);
        $first = reset($columns);
        if ($first['type'] !== 'start') {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'The first column block must be of type "start".'); // @translate
            return;
        }
        $last = end($columns);
        if ($last['type'] !== 'end') {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'The last column block must be of type "end".'); // @translate
            return;
        }

        if (!empty($tagStack)) {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'Some columns have no end.'); // @translate
            return;
        }

        // Update only close, other keys are fixed above.
        $data['close'] = $columns[$block->getPosition()]['close'];

        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['column'];
        $blockFieldset = \BlockPlus\Form\ColumnFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = '<p>'
            . $view->translate('Divide the page into columns. There must be at least two blocks, one for the start and one for the end, to avoid html issues. Multiple columns can be nested.') // @translate
            . '</p>';
        $html .= $view->formCollection($fieldset, false);
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $this->checkBlockData($block, $view);
        if (empty($data['type'])) {
            return '';
        }

        switch ($data['type']) {
            case 'end':
                return "</{$data['close']}>";
            case 'inter':
                $tag = $data['tag'];
                $close = $data['close'];
                $class = $data['class'];
                return $class
                    ? "</$close>\n<$tag class=\"$class\">"
                    : "</$close>\n<$tag>";
            case 'start':
                $tag = $data['tag'];
                $class = $data['class'];
                return $class
                    ? "<$tag class=\"$class\">"
                    : "<$tag>";
            default:
                return '';
        }
    }

    /**
     * @todo Make checks of column blocks during hydration.
     *
     * @param SitePageBlockRepresentation $block
     * @param PhpRenderer $view
     * @return array|false
     */
    protected function checkBlockData(SitePageBlockRepresentation $block, PhpRenderer $view)
    {
        $blockId = $block->id();
        $blockPosition = 0;

        // Check and save the previous tag to close elements quickly.
        $blocks = $block->page()->blocks();
        $columns = [];
        $tagStack = [];
        // Block representation doesn't know its position.
        $position = 0;
        foreach ($blocks as $blk) {
            if ($blk->layout() !== 'column') {
                continue;
            }
            $dta = $blk->data();
            $column = [
                'type' => $dta['type'],
                'tag' => $dta['tag'],
                'class' => $dta['class'],
                'close' => null,
            ];
            switch ($dta['type']) {
                case 'end':
                    if (empty($tagStack)) {
                        $view->logger()->err('Type "intermediate" and "end" columns must be after a block "start" or "intermediate".'); // @translate
                        return false;
                    }
                    $column['close'] = array_pop($tagStack);
                    break;
                case 'inter':
                    if (empty($tagStack)) {
                        $view->logger()->err('Type "intermediate" and "end" columns must be after a block "start" or "intermediate".'); // @translate
                        return false;
                    }
                    $column['close'] = array_pop($tagStack);
                    // no break.
                case 'start':
                    $tagStack[] = $dta['tag'];
                    break;
                default:
                    $view->logger()->err('Unauthorized type for block column.'); // @translate
                    return false;
            }
            $columns[++$position] = $column;
            if ($blockId === $blk->id()) {
                $blockPosition = $position;
            }
        }

        if (count($columns) < 2) {
            $view->logger()->err('A block "column" cannot be single.'); // @translate
            return false;
        }

        ksort($columns);
        $first = reset($columns);
        if ($first['type'] !== 'start') {
            $view->logger()->err('The first column block must be of type "start".'); // @translate
            return false;
        }
        $last = end($columns);
        if ($last['type'] !== 'end') {
            $view->logger()->err('The last column block must be of type "end".'); // @translate
            return false;
        }

        if (!empty($tagStack)) {
            $view->logger()->err('Some columns have no end.'); // @translate
            return false;
        }

        return $columns[$blockPosition];
    }
}
