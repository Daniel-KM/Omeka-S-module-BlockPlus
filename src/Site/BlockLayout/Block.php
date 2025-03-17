<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;

class Block extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/block';

    public function getLabel()
    {
        return 'Simple Block'; // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['block'];
        $blockFieldset = \BlockPlus\Form\BlockFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        $html = '<p>'
            . $view->translate('A simple block allows to display data via one of the templates set in the block layout settings.') // @translate
            . '</p>';
        $html .= $view->formCollection($fieldset, false);
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $vars = ['block' => $block] + $block->data();

        $type = $vars['params_type'] ?? null;
        $type = in_array($type, ['auto', 'raw', 'ini', 'json_array', 'key_value', 'key_value_array']) ? $type : 'auto';
        $vars['params_type'] = $type;

        $params = $vars['params'] ?? '';
        $trimmedParams = trim($params);

        if (!strlen($trimmedParams)) {
            $vars['parameters'] = in_array($params, ['auto', 'raw']) ? $params : [];
            return $view->partial($templateViewScript, $vars);
        }

        // Most of the time, the params are raw, key_value or json_array.
        if ($type === 'auto') {
            if ((mb_substr($trimmedParams, 0, 1) === '{' && mb_substr($trimmedParams, -1) === '}')
                || (mb_substr($trimmedParams, 0, 1) === '[' && mb_substr($trimmedParams, -1) === ']')
            ) {
                $realType = 'json_array';
            } elseif (mb_strpos($trimmedParams, '=')) {
                $realType = 'key_value';
            } else {
                // TODO Manage other formats, but quickly.
                $realType = 'raw';
            }
        } else {
            $realType = $type;
        }

        switch ($realType) {
            default:
            case 'raw':
                $vars['parameters'] = $params;
                break;
            case 'json_array':
                $vars['parameters'] = @json_decode($params, true) ?: [];
                break;
            case 'ini':
                $reader = new \Laminas\Config\Reader\Ini();
                $vars['parameters'] = $reader->fromString($params);
                break;
            case 'key_value':
                $vars['parameters'] = [];
                foreach (array_filter(array_map('trim', explode("\n", $params)), 'strlen') as $keyValue) {
                    [$key, $value] = mb_strpos($keyValue, '=') === false
                        ? [trim($keyValue), '']
                        : array_map('trim', explode('=', $keyValue, 2));
                    if ($key !== '') {
                        $vars['parameters'][$key] = $value;
                    }
                }
                break;
            case 'key_value_array':
                $vars['parameters'] = [];
                foreach (array_map('trim', explode("\n", $params)) as $keyValue) {
                    $vars['parameters'][] = array_map('trim', explode('=', $keyValue, 2)) + ['', ''];
                }
                break;
        }

        return $view->partial($templateViewScript, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags((string) $this->render($view, $block));
    }
}
