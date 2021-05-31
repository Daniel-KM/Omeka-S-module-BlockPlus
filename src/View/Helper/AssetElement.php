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
     * @see \Omeka\View\Helper\Thumbnail
     */
    public function __invoke(AssetRepresentation $asset, array $attribs = []): string
    {
        $mediaType = $asset->mediaType();
        $mainType = strtok($mediaType, '/');

        $url = $asset->assetUrl();
        if (!isset($attribs['alt'])) {
            $attribs['alt'] = '';
        }

        $name = $attribs['name'] ?? $asset->name();
        unset($attribs['name']);

        switch ($mainType) {
            case 'image':
                $attribs['src'] = $url;
                return sprintf('<img%s>', $this->htmlAttribs($attribs));

            case 'video':
                $attribs['src'] = $url;
                $attribs['type'] = $mediaType;
                return sprintf('<video%s controls="controls"></video>', $this->htmlAttribs($attribs));

            case 'audio':
                $attribs['src'] = $url;
                $attribs['type'] = $mediaType;
                return sprintf('<audio%s controls="controls"></audio>', $this->htmlAttribs($attribs));

            default:
                $attribs['url'] = $url;
                $attribs['type'] = $mediaType;
                return sprintf('<span%s>%s</span', $this->htmlAttribs($attribs), htmlentities($name, ENT_NOQUOTES | ENT_HTML5));
        }
    }
}
