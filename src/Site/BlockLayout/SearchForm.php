<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;

class SearchForm extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/search-form';

    public function getLabel()
    {
        return 'Search form'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['searchForm'];
        $blockFieldset = \BlockPlus\Form\SearchFormFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data();
        $vars = ['block' => $block] + $data;

        $searchConfig = $vars['search_config'] ?? '';
        if ($searchConfig === 'omeka') {
            $searchConfig = null;
        } else {
            $searchConfigId = empty($searchConfig) || $searchConfig === 'default' ? null : (int) $searchConfig;
            /** @var \AdvancedSearch\Api\Representation\SearchConfigRepresentation $searchConfig */
            $searchConfig = $view->getSearchConfig($searchConfigId);
            if ($searchConfig && !$searchConfig->form()) {
                $message = new \Omeka\Stdlib\Message(
                    'The search config "%s" has no form associated.', // @translate
                    $searchConfig->path()
                );
                $view->logger()->err($message);
                return '';
            }
        }
        $vars['searchConfig'] = $searchConfig;
        unset($vars['search_config']);

        if (empty($data['link'])) {
            $link = [];
        } else {
            $link = explode(' ', $data['link'], 2);
            $vars['link'] = ['url' => trim($link[0]), 'label' => trim($link[1] ?? '')];
        }

        $template = $vars['template'] ?: self::PARTIAL_NAME;
        unset($vars['template']);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }
}
