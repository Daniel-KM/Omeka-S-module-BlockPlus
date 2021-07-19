<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class Division extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Division'; // @translate
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headScript()
            ->appendFile($assetUrl('js/block-plus.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer']);
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/block-plus.css', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();

        $data['type'] = empty($data['type']) ? 'start' : $data['type'];
        $data['tag'] = empty($data['tag']) ? 'div' : $data['tag'];
        $data['class'] = $data['type'] === 'end'
            ? ''
            // Stricter than w3c standard.
            : preg_replace('/[^A-Za-z0-9_ -]/', '', $data['class']);

        // TODO Find a way to check divisions during hydration, with new blocks. May be possible at least for the last block of the page.
        $block->setData($data);

        if (!$block->getId()) {
            return;
        } else {
            return;
        }

        // Check and save the previous tag to close elements quickly.
        // Blocks are automatically ordered by position (see entity SitePage).
        $blocks = $block->getPage()->getBlocks();
        $divisions = [];
        $tagStack = [];
        foreach ($blocks as $blk) {
            if ($blk->getLayout() !== 'division') {
                continue;
            }

            $dta = $blk->getData();
            $division = [
                'type' => $dta['type'],
                'tag' => $dta['tag'] ?: 'div',
                'class' => $dta['class'],
                'close' => null,
            ];
            switch ($dta['type']) {
                case 'end':
                case 'inter':
                    if (empty($tagStack)) {
                        $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'Type "intermediate" and "end" divisions must be after a block "start" or "intermediate".'); // @translate
                        return;
                    }
                    $division['close'] = array_pop($tagStack);
                    if ($dta['type'] === 'end') {
                        break;
                    }
                    // no break.
                case 'start':
                    $tagStack[] = $dta['tag'];
                    break;
                default:
                    $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'Unauthorized type for block division.'); // @translate
                    return;
            }
            $divisions[$blk->getPosition()] = $division;
        }

        if (count($divisions) < 2) {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'A block "division" cannot be single.'); // @translate
            return;
        }

        ksort($divisions);
        $first = reset($divisions);
        if ($first['type'] !== 'start') {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'The first division block must be of type "start".'); // @translate
            return;
        }
        $last = end($divisions);
        if ($last['type'] !== 'end') {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'The last division block must be of type "end".'); // @translate
            return;
        }

        if (!empty($tagStack)) {
            $errorStore->addError('o:block[__blockIndex__][o:data][type]', 'Some divisions have no end.'); // @translate
            return;
        }

        // Update only close, other keys are fixed above.
        $data['close'] = $divisions[$block->getPosition()]['close'];

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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['division'];
        $blockFieldset = \BlockPlus\Form\DivisionFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = '<p>'
            . $view->translate('Add divisions and classes to wrap one or multiple block.') // @translate
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
     * @todo Make checks of division blocks during hydration.
     */
    protected function checkBlockData(SitePageBlockRepresentation $block, PhpRenderer $view): ?array
    {
        $blockId = $block->id();
        $blockPosition = 0;

        // Check and save the previous tag to close elements quickly.
        $blocks = $block->page()->blocks();
        $divisions = [];
        $tagStack = [];
        // Block representation doesn't know its position.
        $position = 0;
        foreach ($blocks as $blk) {
            if ($blk->layout() !== 'division') {
                continue;
            }
            $dta = $blk->data();
            $division = [
                'type' => $dta['type'],
                'tag' => $dta['tag'],
                'class' => $dta['class'],
                'close' => null,
            ];
            switch ($dta['type']) {
                case 'end':
                    if (empty($tagStack)) {
                        $view->logger()->err('Type "intermediate" and "end" divisions must be after a block "start" or "intermediate".'); // @translate
                        return null;
                    }
                    $division['close'] = array_pop($tagStack);
                    break;
                case 'inter':
                    if (empty($tagStack)) {
                        $view->logger()->err('Type "intermediate" and "end" divisions must be after a block "start" or "intermediate".'); // @translate
                        return null;
                    }
                    $division['close'] = array_pop($tagStack);
                    // no break.
                case 'start':
                    $tagStack[] = $dta['tag'];
                    break;
                default:
                    $view->logger()->err('Unauthorized type for block division.'); // @translate
                    return null;
            }
            $divisions[++$position] = $division;
            if ($blockId === $blk->id()) {
                $blockPosition = $position;
            }
        }

        if (count($divisions) < 2) {
            $view->logger()->err('A block "division" cannot be single.'); // @translate
            return null;
        }

        ksort($divisions);
        $first = reset($divisions);
        if ($first['type'] !== 'start') {
            $view->logger()->err('The first division block must be of type "start".'); // @translate
            return null;
        }
        $last = end($divisions);
        if ($last['type'] !== 'end') {
            $view->logger()->err('The last division block must be of type "end".'); // @translate
            return null;
        }

        if (!empty($tagStack)) {
            $view->logger()->err('Some divisions have no end.'); // @translate
            return null;
        }

        return $divisions[$blockPosition];
    }
}
