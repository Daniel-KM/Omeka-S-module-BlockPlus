<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class Links extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/links';

    public function getLabel()
    {
        return 'Links'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        // TODO The element ArrayTextArea doesn't work in the page form.
        $data = $block->getData();

        $links = $data['links'] ?? [];
        if (is_string($links)) {
            $string = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $links);
            $array = array_filter(array_map('trim', explode("\n", $string)), 'strlen');
            $links = [];
            foreach ($array as $keyValue) {
                if (mb_strpos($keyValue, '=') === false) {
                    $links[trim($keyValue)] = '';
                } else {
                    [$key, $value] = array_map('trim', explode('=', $keyValue, 2));
                    $links[$key] = $value;
                }
            }
        }
        $data['links'] = $links;

        $data = $block->setData($data);
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['links'];
        $blockFieldset = \BlockPlus\Form\LinksFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $vars = ['block' => $block] + $block->data();
        $template = $vars['template'] ?: self::PARTIAL_NAME;
        unset($vars['template']);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags($this->render($view, $block));
    }
}
