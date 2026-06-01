<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class ResourceCard extends AbstractHelper
{
    /**
     * Render a resource card using template-level card config from ART.
     *
     * Fallback chain:
     * - card config per property (ART resource_template_property_data)
     * - site settings (browse_heading_property_term /
     * browse_body_property_term) - defaults (dcterms:title /
     * dcterms:description)
     *
     * @param array $options Keys: mode (grid|list|compact),
     *   show_thumbnail (bool), template_override (string partial path), lang
     *   (array|null for locale filtering).
     */
    public function __invoke(
        AbstractResourceEntityRepresentation $resource,
        array $options = []
    ): string {
        $view = $this->getView();
        $cardConfig = $this->cardConfig($resource);
        $partial = $options['template_override']
            ?? 'common/resource-card';
        return $view->partial($partial, [
            'resource' => $resource,
            'cardConfig' => $cardConfig,
            'options' => $options,
        ]);
    }

    /**
     * Build the card configuration for a resource.
     *
     * Returns an array keyed by role (heading, body, meta, footer), each
     * containing an ordered list of field definitions:
     * [
     *   'term' => 'dcterms:title', 'label' => 'Title' or null, 'first_only' =>
     *   bool, 'separator' => string, 'max' => int,
     * ]
     *
     * @return array|null Null when no ART config (use fallback).
     */
    public function cardConfig(
        AbstractResourceEntityRepresentation $resource
    ): ?array {
        static $cache = [];

        $template = $resource->resourceTemplate();
        if (!$template) {
            return null;
        }

        $templateId = $template->id();
        if (array_key_exists($templateId, $cache)) {
            return $cache[$templateId];
        }

        // Check if the template representation is from ART.
        if (!method_exists($template, 'resourceTemplateProperties')) {
            $cache[$templateId] = null;
            return null;
        }

        $config = [
            'heading' => [],
            'body' => [],
            'meta' => [],
            'footer' => [],
        ];
        $hasConfig = false;

        foreach ($template->resourceTemplateProperties() as $rtp) {
            $mainData = method_exists($rtp, 'mainDataValues')
                ? $rtp->mainDataValues()
                : [];
            if (empty($mainData['card_display'])
                || $mainData['card_display'] !== 'yes'
            ) {
                continue;
            }

            $hasConfig = true;
            $role = $mainData['card_role'] ?? 'body';
            if (!isset($config[$role])) {
                $role = 'body';
            }

            $property = $rtp->property();
            $config[$role][] = [
                'term' => $property->term(),
                'label' => $rtp->alternateLabel()
                    ?: $property->label(),
                'first_only' => !empty($mainData['card_first_only'])
                    && $mainData['card_first_only'] === 'yes',
                'separator' => $mainData['card_separator'] ?? ', ',
                'max' => (int) ($mainData['card_max'] ?? 0),
            ];
        }

        $cache[$templateId] = $hasConfig ? $config : null;
        return $cache[$templateId];
    }
}
