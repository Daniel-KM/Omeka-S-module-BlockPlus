<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class RedirectToUrl extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    public function getLabel()
    {
        return 'Redirect to url'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['redirectToUrl'];
        $blockFieldset = \BlockPlus\Form\RedirectToUrlFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $url = (string) $block->dataValue('url');
        if (substr($url, 0, 4) !== 'http'
            || !filter_var($url, FILTER_VALIDATE_URL)
            || strncmp('cli', PHP_SAPI, 3) === 0
        ) {
            return $view->escapeHtml(sprintf(
                $view->translate('Redirect to %s'), // @translate
                $url
            ));
        }
        header('Location: ' . $url);
        exit;
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return sprintf(
            $view->translate('Redirect to %s'),
            sprintf('<a href="%1$s">%1$s</a>', $view->escapeHtmlAttr($block->dataValue('url')))
        );
    }
}
