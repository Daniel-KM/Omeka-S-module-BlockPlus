<?php declare(strict_types=1);

namespace BlockPlus\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

/**
 * A button to display buttons to share current page.
 */
class Buttons implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Buttons'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        $buttons = $view->siteSetting('blockplus_block_buttons') ?: [];
        $buttons = $this->shareLinks($buttons);
        return $view->partial('common/resource-page-block-layout/buttons', [
            'resource' => $resource,
            'buttons' => $buttons,
        ]);
    }

    /**
     * Adapted in:
     * @see BlockPlus\Site\BlockLayout\Buttons::shareLinks()
     * @see BlockPlus\Site\ResourcePageBlockLayout\Buttons::shareLinks()
     */
    public function shareLinks(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $buttons): array
    {
        if (!$buttons) {
            return [];
        }

        $result = [];

        $plugins = $view->getHelperPluginManager();
        $site = $plugins->get('currentSite')();
        $translate = $plugins->get('translate');
        $siteSetting = $plugins->get('siteSetting');

        $filterLocale = (bool) $siteSetting('filter_locale_values');
        $lang = $filterLocale ? $this->lang() : null;
        $langValue = $filterLocale ? [$lang, ''] : null;

        $siteSlug = $site->slug();
        $siteTitle = $site->title();
        $url = $resource->siteUrl($siteSlug, true);
        $title = $resource->displayTitle(null, $langValue);

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
