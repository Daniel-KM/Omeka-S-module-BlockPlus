<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHtmlElement as AbstractHtmlElementHelper;
use Omeka\Api\Representation\AssetRepresentation;

/**
 * Create a tag for an advanced asset (image, audio, video), or a span.
 */
class AssetElement extends AbstractHtmlElementHelper
{
    /**
     * Render an asset of any type (image, audio, video), or a span.
     *
     * For images, uses trigger "view_helper.thumbnail.attribs" like thumbnail().
     *
     * @see \Omeka\View\Helper\Thumbnail
     */
    public function __invoke(AssetRepresentation $asset, $type = 'square', array $attribs = []): string
    {
        $mediaType = $asset->mediaType();
        $mainType = strtok($mediaType, '/');

        $url = $asset->assetUrl();

        // For compatibility with old themes.
        if (is_array($type)) {
            $attribs = $type;
            $type = 'square';
        }

        // Use same process than thumbnail to be replaceable.
        if ($mainType === 'image') {
            $attribs['src'] = $url;
            // Trigger attribs event
            $representation = $asset;
            $triggerHelper = $this->getView()->plugin('trigger');
            $params = compact('attribs', 'representation', 'type');
            $params = $triggerHelper('view_helper.thumbnail.attribs', $params, true);
            $attribs = $params['attribs'];
        }

        $attribs['alt'] ??= $asset->thumbnailAltText();

        $name = $attribs['name'] ?? $asset->name();
        unset($attribs['name']);

        switch ($mainType) {
            case 'image':
                // Include element for lazy loading. See https://developer.mozilla.org/en-US/docs/Web/API/HTMLImageElement/loading
                if (!isset($attribs['loading'])) {
                    // Due to a bug in firefox, the attribute "loading" should be set
                    // before src (see https://bugzilla.mozilla.org/show_bug.cgi?id=1647077).
                    $attribs = ['loading' => 'lazy'] + $attribs;
                }
                return sprintf('<img%s>', $this->htmlAttribs($attribs));

            case 'video':
                $attribs['src'] = $url;
                $attribs['type'] = $mediaType;
                $attribs['preload'] ??= 'none';
                return sprintf('<video%s controls="controls"></video>', $this->htmlAttribs($attribs));

            case 'audio':
                $attribs['src'] = $url;
                $attribs['type'] = $mediaType;
                $attribs['preload'] ??= 'none';
                return sprintf('<audio%s controls="controls"></audio>', $this->htmlAttribs($attribs));

            case $mediaType === 'application/pdf':
                $attribs['src'] = $url;
                if (!isset($attribs['loading'])) {
                    $attribs = ['loading' => 'lazy'] + $attribs;
                }
                $attribs['style'] ??= 'width: 100%%; height: 600px';
                return sprintf('<iframe%s allowfullscreen></iframe>', $this->htmlAttribs($attribs));

            default:
                $attribs['url'] = $url;
                $attribs['type'] = $mediaType;
                return sprintf('<span%s>%s</span', $this->htmlAttribs($attribs), htmlentities($name, ENT_NOQUOTES | ENT_HTML5));
        }
    }
}
