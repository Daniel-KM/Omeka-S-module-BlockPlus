<?php declare(strict_types=1);

namespace BlockPlus\Controller\Site;

use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\MediaRepresentation;
use ZipStream\ZipStream;

/**
 * Controller to handle resource file downloads, with zip streaming.
 */
class DownloadController extends AbstractActionController
{
    /**
     * Stream a resource download (single file or zip archive).
     */
    public function downloadAction()
    {
        $resourceType = $this->params('resource-type');
        $resourceId = $this->params('resource-id');
        $content = $this->params()->fromQuery('content', 'primary');
        $type = $this->params()->fromQuery('type', 'original');
        $singleAsFile = (bool) $this->params()->fromQuery('single_as_file', false);

        // Check if download is enabled for this site.
        $siteSettings = $this->siteSettings();
        if (!$siteSettings->get('blockplus_download_enabled', false)) {
            $this->logger()->warn(
                'Attempt to download zip for resource {resource_type} #{resource_id} on site "{site}" without download enabled.', // @translate
                [
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId,
                    'site' => $this->currentSite()->slug(),
                ]
            );
            return $this->notFoundAction();
        }

        // Validate resource type.
        $validTypes = ['item', 'media'];
        if (!in_array($resourceType, $validTypes)) {
            return $this->notFoundAction();
        }

        // Validate type.
        // TODO Get the list of thumbnail types from the config.
        $validFileTypes = ['original', 'large', 'medium', 'square'];
        if (!in_array($type, $validFileTypes)) {
            $type = 'original';
        }

        // Get the right resource name (items/media).
        $resourceType = $this->easyMeta()->resourceName($resourceType);

        // Get the resource.
        try {
            $resource = $this->api()->read($resourceType, $resourceId)->getContent();
        } catch (\Exception $e) {
            return $this->notFoundAction();
        }

        // Get downloadable medias.
        $medias = $this->getDownloadableMedias($resource, $content);
        if (!$medias) {
            return $this->notFoundAction();
        }

        // Determine if single file or zip.
        // Default is always zip. Single file output only when option is enabled.
        $isSingleFile = $singleAsFile
            && ($content === 'primary' || count($medias) === 1);

        // Check if ZipStream is available for zip output.
        $hasZipStream = class_exists(\ZipStream\ZipStream::class);

        if ($medias && $isSingleFile) {
            return $this->streamSingleFile(reset($medias), $type, $resource);
        }

        // Fallback to single file if ZipStream is not available.
        if (!$hasZipStream) {
            if (count($medias) === 1) {
                return $this->streamSingleFile(reset($medias), $type, $resource);
            }
            // Cannot create zip without ZipStream library.
            $this->messenger()->addError(
                'The ZipStream library is required to download multiple files as zip.' // @translate
            );
            return $this->redirect()->toRoute('site/resource-id', [
                'controller' => $resourceType === 'items' ? 'item' : 'media',
                'action' => 'show',
                'id' => $resourceId,
            ], [], true);
        }

        return $this->streamZipArchive($medias, $type, $resource);
    }

    /**
     * Stream a single file directly.
     */
    protected function streamSingleFile(
        MediaRepresentation $media,
        string $type,
        $resource
    ): HttpResponse {
        $filepath = $this->getMediaFilePath($media, $type);
        if (!$filepath || !file_exists($filepath)) {
            return $this->notFoundAction();
        }

        $filename = $this->buildFilename($resource, true, $type, $media);

        // Use Common's SendFile plugin if available.
        return $this->sendFile($filepath, [
            'filename' => $filename,
            'disposition_mode' => 'attachment',
            'resource' => $media,
            'storage_type' => $type,
        ]);
    }

    /**
     * Stream a zip archive with all files.
     */
    protected function streamZipArchive(
        array $medias,
        string $type,
        $resource
    ): HttpResponse {
        $filename = $this->buildFilename($resource, false, $type);

        // Get copyright text.
        $copyrightText = $this->buildCopyrightText($resource, $medias, $type);

        // Get response and set headers.
        /** @var \Laminas\Http\PhpEnvironment\Response $response */
        $response = $this->getResponse();
        $headers = $response->getHeaders();

        $headers
            ->addHeaderLine('Content-Type: application/zip')
            ->addHeaderLine(sprintf('Content-Disposition: attachment; filename="%s"', $filename))
            ->addHeaderLine('Content-Transfer-Encoding: binary')
            ->addHeaderLine('Cache-Control: no-cache, no-store, must-revalidate')
            ->addHeaderLine('Pragma: no-cache')
            ->addHeaderLine('Expires: 0');

        // Fix deprecated warning.
        $errorReporting = error_reporting();
        error_reporting($errorReporting & ~E_DEPRECATED);

        $response->sendHeaders();

        error_reporting($errorReporting);

        // Clear output buffers.
        $response->setContent('');
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Stream the zip using ZipStream.
        // Use STORE (no compression) by default since media files (images,
        // pdf, video) are already compressed.
        $zip = new ZipStream(
            // Use standard order for compatibility of the module with php 7.4.
            /*
            outputName: $filename,
            sendHttpHeaders: false,
            defaultCompressionMethod: CompressionMethod::STORE,
            */
            null,
            '',
            null,
            0,
            6,
            true,
            true,
            false,
            null,
            $filename
        );

        // Add copyright file if configured (use DEFLATE for text).
        if ($copyrightText) {
            $zip->addFile(
                /*
                fileName: 'COPYRIGHT.txt',
                data: $copyrightText,
                compressionMethod: CompressionMethod::DEFLATE,
                */
                'COPYRIGHT.txt',
                $copyrightText
            );
        }

        // Add each media file (no compression, already compressed formats).
        foreach ($medias as $index => $media) {
            $filepath = $this->getMediaFilePath($media, $type);
            if (!$filepath || !file_exists($filepath)) {
                continue;
            }

            $mediaFilename = $this->buildMediaFilename($media, $type, $index);

            // Stream file from disk without compression.
            $zip->addFileFromPath(
                /*
                fileName: $mediaFilename,
                path: $filepath,
                */
                $mediaFilename,
                $filepath
            );
        }

        $zip->finish();

        // Prevent further output.
        ini_set('display_errors', '0');

        return $response;
    }

    /**
     * Get downloadable medias from a resource.
     */
    protected function getDownloadableMedias($resource, string $content): array
    {
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
     * Get file path for a media.
     */
    protected function getMediaFilePath(MediaRepresentation $media, string $type): ?string
    {
        $basePath = OMEKA_PATH . '/files/';

        if ($type === 'original') {
            $filepath = $basePath . 'original/' . $media->filename();
        } else {
            $storageId = $media->storageId();
            $filepath = $basePath . $type . '/' . $storageId . '.jpg';
        }

        return file_exists($filepath) ? $filepath : null;
    }

    /**
     * Build download filename.
     */
    protected function buildFilename(
        $resource,
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
     * Build filename for a media inside the zip.
     */
    protected function buildMediaFilename(
        MediaRepresentation $media,
        string $type,
        int $index
    ): string {
        $title = $media->displayTitle();
        // Sanitize filename.
        $title = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $title);
        $title = preg_replace('/\s+/', '_', $title);
        $title = substr($title, 0, 80);

        if ($type === 'original') {
            $extension = pathinfo($media->filename(), PATHINFO_EXTENSION);
        } else {
            $extension = 'jpg';
        }

        // Add index to avoid filename conflicts.
        return sprintf('%02d_%s.%s', $index + 1, $title, $extension);
    }

    /**
     * Build copyright text with placeholders.
     */
    protected function buildCopyrightText($resource, array $medias, string $type): ?string
    {
        $siteSettings = $this->siteSettings();
        $template = $siteSettings->get('blockplus_download_zip_text', '');

        if (empty($template)) {
            return null;
        }

        $matches = [];

        // Build placeholders.
        $placeholders = [
            '{file_count}' => (string) count($medias),
            '{resource_id}' => (string) $resource->id(),
            '{resource_title}' => $resource->displayTitle(),
            '{resource_url}' => $resource->siteUrl(null, true),
            '{file_type}' => $type,
        ];

        // Add site info.
        $site = $this->currentSite();
        if ($site) {
            $placeholders['{site_title}'] = $site->title();
            $placeholders['{site_url}'] = $site->siteUrl(null, true);
        }

        // Add installation title.
        $settings = $this->settings();
        $placeholders['{main_title}'] = $settings->get('installation_title', 'Omeka S');

        // Add date.
        $placeholders['{date}'] = date('Y-m-d');
        $placeholders['{datetime}'] = date('Y-m-d H:i:s');

        // Add citation if available.
        $placeholders['{citation}'] = $this->buildCitation($resource);

        // Add property values from resource.
        // Match patterns like {dcterms:creator}, {dcterms:date}, etc.
        if (preg_match_all('/\{([a-zA-Z0-9_-]+:[a-zA-Z0-9_-]+)\}/', $template, $matches)) {
            foreach ($matches[1] as $term) {
                $values = $resource->value($term, ['all' => true]) ?: [];
                $values = array_map('strip_tags', $values);
                $placeholders['{' . $term . '}'] = implode(', ', $values);
            }
        }

        // Apply placeholders.
        $text = strtr($template, $placeholders);

        return $text;
    }

    /**
     * Build citation text for a resource.
     */
    protected function buildCitation($resource): string
    {
        $translate = $this->viewHelpers()->get('translate');

        $citation = '';

        // Creator.
        $creators = $resource->value('dcterms:creator', ['all' => true]) ?: [];
        $creators = array_values(array_filter(array_map('strip_tags', $creators)));
        if ($creators) {
            switch (count($creators)) {
                case 1:
                    $creator = $creators[0];
                    break;
                case 2:
                    $creator = sprintf($translate('%1$s and %2$s'), $creators[0], $creators[1]);
                    break;
                case 3:
                    $creator = sprintf($translate('%1$s, %2$s, and %3$s'), $creators[0], $creators[1], $creators[2]);
                    break;
                default:
                    $creator = sprintf($translate('%s et al.'), $creators[0]);
                    break;
            }
            $citation .= $creator;
        }

        // Title.
        $title = $resource->displayTitle();
        $citation .= ($citation ? ', ' : '') . '"' . $title . '"';

        // Publisher.
        $publisher = $resource->value('dcterms:publisher');
        if ($publisher) {
            $citation .= ', ' . $publisher;
        }

        // Date.
        $date = $resource->value('dcterms:date');
        if ($date) {
            $citation .= ', ' . $date;
        }

        // Url.
        $citation .= ', ' . $resource->siteUrl(null, true);

        // Access date.
        $citation .= '. ' . sprintf($translate('Accessed on %s'), date('Y-m-d'));

        return $citation;
    }
}
