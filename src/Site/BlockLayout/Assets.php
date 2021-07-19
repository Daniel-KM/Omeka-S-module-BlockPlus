<?php declare(strict_types=1);
namespace BlockPlus\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;

class Assets extends AbstractBlockLayout
{
    use CommonTrait;

    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/assets';

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(
        HtmlPurifier $htmlPurifier
    ) {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function getLabel()
    {
        return 'Assets'; // @translate
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()->appendStylesheet($assetUrl('css/asset-form.css', 'Omeka'));
        $view->headScript()
            ->appendFile($assetUrl('js/asset-form.js', 'Omeka'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('js/block-plus.js', 'BlockPlus'), 'text/javascript', ['defer' => 'defer']);
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();

        if (!isset($data['assets'])) {
            $data['assets'] = [];
        }

        // Normalize values and purify html.
        $data['assets'] = array_map(function ($v) {
            $v += ['asset' => null, 'caption' => null, 'title' => null, 'url' => null, 'class' => null];
            $v['caption'] = isset($v['caption'])
                ? $this->fixEndOfLine($this->htmlPurifier->purify($v['caption']))
                : '';
            // Stricter than w3c standard.
            $v['class'] = preg_replace('/[^A-Za-z0-9_ -]/', '', $v['class']);
            return $v;
        }, $data['assets']);

        // Trim all values, then remove empty asset arrays: array without asset
        // and caption are removed.
        $data['assets'] = array_values(array_filter(
            array_map(function ($v) {
                return array_map('trim', $v);
            }, $data['assets']),
            function ($asset) {
                return !empty($asset['asset']) || !empty($asset['caption']);
            }
        ));

        $data = $block->setData($data);
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['assets'];
        $fieldset = $formElementManager->get(\BlockPlus\Form\AssetsFieldset::class);

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            // Add fields for repeatable fieldsets with multiple fields.
            if (is_array($value)) {
                $subFieldsetName = "o:block[__blockIndex__][o:data][$key]";
                /** @var \Laminas\Form\Fieldset $subFieldset */
                $subFieldset = $fieldset->get($subFieldsetName);
                $subFieldsetBaseName = $subFieldsetName . '[__' . substr($key, 0, -1) . 'Index__]';
                /** @var \Laminas\Form\Fieldset $subFieldsetBase */
                $subFieldsetBase = $subFieldset->get($subFieldsetBaseName);
                foreach (array_values($value) as $subKey => $subValue) {
                    $newSubFieldsetName = $subFieldsetName . "[$subKey]";
                    /** @var \Laminas\Form\Fieldset $newSubFieldset */
                    $newSubFieldset = clone $subFieldsetBase;
                    $newSubFieldset
                        ->setName($newSubFieldsetName)
                        ->setAttribute('data-index', $subKey);
                    $subFieldset->add($newSubFieldset);
                    foreach ($subValue as $subSubKey => $subSubValue) {
                        $elementBaseName = $subFieldsetBaseName . "[$subSubKey]";
                        $elementName = "o:block[__blockIndex__][o:data][$key][$subKey][$subSubKey]";
                        $newSubFieldset
                            ->get($elementBaseName)
                            ->setName($elementName)
                            ->setValue($subSubValue);
                        $dataForm[$elementName] = $subSubValue;
                    }
                    // $newSubFieldset->populateValues($dataForm);
                }
                $subFieldset
                    ->remove($subFieldsetBaseName)
                    ->setAttribute('data-next-index', count($value));
            } else {
                $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
            }
        }

        $fieldset->populateValues($dataForm);

        // The assets are currently filled manually (use default form).

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $api = $view->api();
        $assets = $block->dataValue('assets', []);

        foreach ($assets as $key => &$assetData) {
            // Get the asset.
            if (empty($assetData['asset'])) {
                continue;
            }

            try {
                $assetData['asset'] = $api->read('assets', $assetData['asset'])->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                $assetData['asset'] = null;
            }

            if (empty($assetData['asset']) && empty($assetData['caption'])) {
                unset($assets[$key]);
            }
        }

        $vars = [
            'heading' => $block->dataValue('heading', ''),
            'assets' => $assets,
        ];

        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $template !== self::PARTIAL_NAME && $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $fulltext = $block->dataValue('heading', '');
        foreach ($block->dataValue('slides', []) as $slide) {
            $fulltext .= ' ' . $slide['start_date']
                . ' ' . $slide['title']
                . ' ' . $slide['caption'];
        }
        return $fulltext;
    }
}
