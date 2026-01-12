<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Common\Stdlib\PsrMessage;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\MediaRepresentation;

/**
 * View helper to display a download link with zip archive for resources.
 *
 * When clicked, a dialog shows the file size and asks for confirmation.
 * The zip is streamed in real-time without storage.
 */
class DownloadZip extends AbstractHelper
{
    /**
     * @var bool
     */
    protected static $assetsLoaded = false;

    /**
     * Render a download link for a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource The resource to download.
     * @param array $options Override site settings:
     *   - content: 'primary' (single file) or 'all' (zip of all medias)
     *   - type: 'original', 'large', 'medium', 'square'
     *   - single_as_file: bool, output single file as native file instead of zip
     *   - label: Link label (default: translated 'Download')
     *   - class: Additional CSS classes
     *   - attributes: Additional HTML attributes
     * @return string HTML output.
     */
    public function __invoke(
        AbstractResourceEntityRepresentation $resource,
        array $options = []
    ): string {
        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $url = $plugins->get('url');
        $escape = $plugins->get('escapeHtml');
        $translate = $plugins->get('translate');
        $siteSetting = $plugins->get('siteSetting');

        // Check if download is enabled for this site.
        if (!$siteSetting('blockplus_download_enabled', false)) {
            return '';
        }

        // Get options from site settings or override.
        $content = $options['content'] ?? $siteSetting('blockplus_download_content', 'all');
        $type = $options['type'] ?? $siteSetting('blockplus_download_type', 'original');
        $singleAsFile = $options['single_as_file'] ?? (bool) $siteSetting('blockplus_download_single_as_file', false);
        $label = $options['label'] ?? $translate('Download');
        $class = $options['class'] ?? '';
        $attributes = $options['attributes'] ?? [];

        // Check if resource has downloadable medias.
        $medias = $this->getDownloadableMedias($resource, $content);
        if (empty($medias)) {
            return '';
        }

        // Check if zip output will be needed.
        // Zip streaming requires PHP 8.1+ (ZipStream v3).
        $isSingleMedia = $content === 'primary' || count($medias) === 1;
        $needsZip = !$singleAsFile || !$isSingleMedia;
        if ($needsZip && PHP_VERSION_ID < 80100) {
            return '';
        }

        // Load JavaScript assets once.
        $this->loadAssets();

        // Calculate total file size.
        $totalSize = $this->calculateTotalSize($medias, $type);
        $formattedSize = $this->formatFileSize($totalSize);

        // Determine if it's a single file or zip.
        // Default is always zip. Single file output only when option is enabled.
        $isSingleFile = $singleAsFile && $isSingleMedia;

        // Build download URL.
        $query = [
            'content' => $content,
            'type' => $type,
        ];
        if ($singleAsFile) {
            $query['single_as_file'] = '1';
        }
        $downloadUrl = $url('site/download-zip', [
            'resource-type' => $resource->resourceName() === 'items' ? 'item' : 'media',
            'resource-id' => $resource->id(),
        ], [
            'query' => $query,
        ]);

        // Build filename.
        $filename = $this->buildFilename($resource, $isSingleFile, $type, $medias ? reset($medias) : null);

        // Build dialog message.
        $mediaCount = count($medias);
        if ($isSingleFile) {
            $dialogMessage = new PsrMessage(
                'Download file: {filename} ({size})', // @translate
                ['filename' => $escape($filename), 'size' => $formattedSize]
            );
        } else {
            $dialogMessage = new PsrMessage(
                'Download {count} files as zip: {filename} ({size})', // @translate
                ['count' => $mediaCount, 'filename' => $escape($filename), 'size' => $formattedSize]
            );
        }
        $dialogMessage = $translate($dialogMessage);

        return $view->partial('common/download-zip', [
            'site' => $view->currentSite(),
            'resource' => $resource,
            'medias' => $medias,
            'downloadUrl' => $downloadUrl,
            'filename' => $filename,
            'label' => $label,
            'class' => $class,
            'attributes' => $attributes,
            'dialogMessage' => $dialogMessage,
            'totalSize' => $totalSize,
            'formattedSize' => $formattedSize,
            'mediaCount' => $mediaCount,
            'isSingleFile' => $isSingleFile,
        ]);
    }

    /**
     * Get downloadable medias from a resource.
     */
    protected function getDownloadableMedias(
        AbstractResourceEntityRepresentation $resource,
        string $content
    ): array {
        $medias = [];

        if ($resource instanceof MediaRepresentation) {
            if ($resource->hasOriginal()) {
                $medias[] = $resource;
            }
        } elseif ($resource instanceof ItemRepresentation) {
            if ($content === 'primary') {
                $primary = $resource->primaryMedia();
                if ($primary && $primary->hasOriginal()) {
                    $medias[] = $primary;
                }
            } else {
                foreach ($resource->media() as $media) {
                    if ($media->hasOriginal()) {
                        $medias[] = $media;
                    }
                }
            }
        }

        return $medias;
    }

    /**
     * Calculate total file size for medias.
     */
    protected function calculateTotalSize(array $medias, string $type): int
    {
        $totalSize = 0;
        foreach ($medias as $media) {
            $size = $this->getMediaFileSize($media, $type);
            if ($size) {
                $totalSize += $size;
            }
        }
        return $totalSize;
    }

    /**
     * Get file size for a media.
     */
    protected function getMediaFileSize(MediaRepresentation $media, string $type): int
    {
        $basePath = OMEKA_PATH . '/files/';

        if ($type === 'original') {
            $filepath = $basePath . 'original/' . $media->filename();
        } else {
            $storageId = $media->storageId();
            $filepath = $basePath . $type . '/' . $storageId . '.jpg';
        }

        if (file_exists($filepath)) {
            return (int) filesize($filepath);
        }

        return 0;
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } elseif ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . ' MB';
        } else {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
    }

    /**
     * Build download filename.
     */
    protected function buildFilename(
        AbstractResourceEntityRepresentation $resource,
        bool $isSingleFile,
        string $type,
        ?MediaRepresentation $media = null
    ): string {
        $title = $resource->displayTitle();
        // Sanitize filename.
        $title = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $title);
        $title = preg_replace('/\s+/', '_', $title);
        $title = substr($title, 0, 100);

        if ($isSingleFile && $media) {
            if ($type === 'original') {
                $extension = pathinfo($media->filename(), PATHINFO_EXTENSION);
            } else {
                $extension = 'jpg';
            }
            return $title . '.' . $extension;
        }

        return $title . '.zip';
    }

    /**
     * Load JavaScript assets for download dialog.
     */
    protected function loadAssets(): void
    {
        if (self::$assetsLoaded) {
            return;
        }

        self::$assetsLoaded = true;

        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');

        // Requires Common module for dialog.
        $view->headScript()
            ->appendFile($assetUrl('js/common-dialog.js', 'Common'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/download-zip.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer']);
        $view->headLink()
            ->appendStylesheet($assetUrl('css/common-dialog.css', 'Common'));
    }
}
