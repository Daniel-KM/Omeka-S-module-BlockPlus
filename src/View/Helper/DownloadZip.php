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
     * Render a download link for a resource or resources from a query.
     *
     * @param AbstractResourceEntityRepresentation|array $resourceOrQuery The resource to download
     *   or an API query array (e.g. ['resource_class_id' => 1, 'property' => [...]])
     * @param array $options Override site settings:
     *   - resource_type: 'resource', 'item' or 'media' (default: 'resource', used for query)
     *   - content: 'primary' (single file) or 'all' (zip of all medias)
     *   - type: 'original', 'large', 'medium', 'square'
     *   - single_as_file: bool, output single file as native file instead of zip
     *   - label: Link label (default: translated 'Download')
     *   - class: Additional CSS classes
     *   - attributes: Additional HTML attributes
     * @return string HTML output.
     */
    public function __invoke(
        $resourceOrQuery,
        array $options = []
    ): string {
        // Handle query-based download.
        if (is_array($resourceOrQuery)) {
            return $this->renderQueryDownload($resourceOrQuery, $options);
        }

        /** @var \Omeka\Api\Representation\AbstractResourceEntityRepresentation $resourceOrQuery */
        return $this->renderResourceDownload($resourceOrQuery, $options);
    }

    /**
     * Render download link for a single resource.
     */
    protected function renderResourceDownload(
        AbstractResourceEntityRepresentation $resource,
        array $options
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
            'resources' => [],
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
            'resourceCount' => 0,
            'isSingleFile' => $isSingleFile,
            'isQuery' => false,
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

    /**
     * Render download link for resources from an api query.
     *
     * @param array $query Api query parameters (same as api->search())
     * @param array $options Options including:
     *   - resource_type: 'resources', 'items' or 'media' (default: 'resources')
     *   - type: 'original', 'large', 'medium', 'square'
     *   - label: Link label
     *   - class: Additional css classes
     *   - attributes: Additional html attributes
     */
    protected function renderQueryDownload(array $query, array $options): string
    {
        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $url = $plugins->get('url');
        $escape = $plugins->get('escapeHtml');
        $translate = $plugins->get('translate');
        $siteSetting = $plugins->get('siteSetting');
        $api = $plugins->get('api');
        $easyMeta = $plugins->get('easyMeta');

        // Check if download is enabled for this site.
        if (!$siteSetting('blockplus_download_enabled', false)) {
            return '';
        }

        // Zip streaming requires PHP 8.1+ (ZipStream v3).
        if (PHP_VERSION_ID < 80100) {
            return '';
        }

        // Get options (singular in route, plural for api).
        $resourceType = $options['resource_type'] ?? 'resources';
        $type = $options['type'] ?? $siteSetting('blockplus_download_type', 'original');
        $label = $options['label'] ?? $translate('Download');
        $class = $options['class'] ?? '';
        $attributes = $options['attributes'] ?? [];

        // Get the right resource name (resources/items/media).
        $resourceType = $easyMeta->resourceName($resourceType);

        // Validate resource type.
        if (!in_array($resourceType, ['resources', 'items', 'medias'])) {
            return '';
        }

        // Apply limit from site settings.
        $maxResources = (int) $siteSetting('blockplus_download_max', 25);
        if ($maxResources > 0) {
            $query['limit'] = min($query['limit'] ?? $maxResources, $maxResources);
        }

        // Add site constraint.
        $site = $view->currentSite();
        if ($site) {
            $query['site_id'] = $site->id();
        }

        // Search resources to calculate size and count.
        try {
            $response = $api->search($resourceType, $query);
            $resources = $response->getContent();
        } catch (\Exception $e) {
            return '';
        }

        if (empty($resources)) {
            return '';
        }

        // Collect all downloadable medias.
        $allMedias = [];
        foreach ($resources as $resource) {
            $medias = $this->getDownloadableMedias($resource, 'all');
            foreach ($medias as $media) {
                $allMedias[] = $media;
            }
        }

        if (empty($allMedias)) {
            return '';
        }

        // Load JavaScript assets once.
        $this->loadAssets();

        // Calculate total file size.
        $totalSize = $this->calculateTotalSize($allMedias, $type);
        $formattedSize = $this->formatFileSize($totalSize);

        // Build download url with query parameters.
        $urlQuery = $query;
        $urlQuery['type'] = $type;
        // Site is added by controller.
        unset($urlQuery['site_id']);

        $downloadUrl = $url('site/download-zip-query', [
            'resource-type' => $resourceType,
        ], [
            'query' => $urlQuery,
        ]);

        // Build filename.
        $filename = $this->sanitizeFilename($site ? $site->title() : 'download') . '_' . date('Ymd_His') . '.zip';

        // Build dialog message.
        $mediaCount = count($allMedias);
        $resourceCount = count($resources);
        $dialogMessage = new PsrMessage(
            'Download {file_count} files from {resource_count} resources as zip: {filename} ({size})', // @translate
            [
                'file_count' => $mediaCount,
                'resource_count' => $resourceCount,
                'filename' => $escape($filename),
                'size' => $formattedSize,
            ]
        );
        $dialogMessage = $translate($dialogMessage);

        return $view->partial('common/download-zip', [
            'site' => $site,
            'resource' => null,
            'resources' => $resources,
            'medias' => $allMedias,
            'downloadUrl' => $downloadUrl,
            'filename' => $filename,
            'label' => $label,
            'class' => $class,
            'attributes' => $attributes,
            'dialogMessage' => $dialogMessage,
            'totalSize' => $totalSize,
            'formattedSize' => $formattedSize,
            'mediaCount' => $mediaCount,
            'resourceCount' => $resourceCount,
            'isSingleFile' => false,
            'isQuery' => true,
        ]);
    }

    /**
     * Sanitize a string for use as filename.
     */
    protected function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $name);
        $name = preg_replace('/\s+/', '_', $name);
        return substr($name, 0, 100) ?: 'download';
    }
}
