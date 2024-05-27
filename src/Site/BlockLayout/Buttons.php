<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class Buttons extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/buttons';

    public function getLabel()
    {
        return 'Buttons'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['buttons'];
        $blockFieldset = \BlockPlus\Form\ButtonsFieldset::class;

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
        $vars = ['block' => $block] + $block->data();
        $vars['buttons'] = $this->shareLinks($view, $block->page(), $vars['buttons'] ?? []);
        return $view->partial($templateViewScript, $vars);
    }

    public function shareLinks(PhpRenderer $view, SitePageRepresentation $page, array $buttons): array
    {
        if (!$buttons) {
            return [];
        }

        $result = [];

        $translate = $view->plugin('translate');
        $site = $page->site();
        $siteSlug = $site->slug();
        $siteTitle = $site->title();
        $url = $page->siteUrl($siteSlug, true);
        $title = $page->title();

        $encodedUrl = rawurlencode($url);
        $encodedTitle = rawurlencode($title);

        $onclick = "javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');return false;";

        foreach ($buttons as $button) {
            $data = [];
            switch ($button) {
                case 'download':
                    $data = [
                        'label' => $translate('Download'), // @translate
                        'attrs' => [
                            'id' => 'button-print',
                            'href' => '#',
                            'title' => $translate('Download'), // @translate
                            'onclick' => 'window.print(); return false;',
                            'class' => 'share-page icon-download',
                            'tabindex' => '0',
                        ],
                    ];
                    break;

                case 'email':
                    $data = [
                        'label' => $translate('Email'), // @translate
                        'attrs' => [
                            'id' => 'button-email',
                            'href' => 'mailto:?subject=' . $encodedTitle . '&body=' . rawurlencode(sprintf($translate("%s%s\n-\n%s"), $siteTitle, $title === $siteTitle ? '' : "\n-\n" . $title, $url)),
                            'title' => $translate('Share by mail'), // @translate
                            'class' => 'share-page icon-mail',
                            'tabindex' => '0',
                        ],
                    ];
                    break;

                case 'facebook':
                    $data = [
                        'label' => 'Facebook',
                        'attrs' => [
                            'id' => 'button-facebook',
                            'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl . '&t=' . $encodedTitle,
                            'title' => $translate('Share on Facebook'), // @translate
                            'onclick' => $onclick,
                            'target' => '_blank',
                            'class' => 'share-page icon-facebook',
                            'tabindex' => '0',
                        ],
                    ];
                    break;

                case 'pinterest':
                    $data = [
                        'label' => 'Pinterest',
                        'attrs' => [
                            'id' => 'button-pinterest',
                            'href' => 'https://pinterest.com/pin/create/link/?url=' . $encodedUrl . '&description=' . $encodedTitle,
                            'title' => $translate('Share on Pinterest'), // @translate
                            'onclick' => $onclick,
                            'target' => '_blank',
                            'class' => 'share-page icon-pinterest',
                            'tabindex' => '0',
                        ],
                    ];
                    break;

                case 'twitter':
                    $data = [
                        'label' => 'Twitter',
                        'attrs' => [
                            'id' => 'button-twitter',
                            'href' => 'https://twitter.com/share?url=' . $encodedUrl . '&text=' . $encodedTitle,
                            'title' => $translate('Share on Twitter'), // @translate
                            'onclick' => $onclick,
                            'target' => '_blank',
                            'class' => 'share-page icon-twitter',
                            'tabindex' => '0',
                        ],
                    ];
                    break;

                default:
                    continue 2;
            }
            $result[$button] = $data;
        }

        return $result;
    }
}
