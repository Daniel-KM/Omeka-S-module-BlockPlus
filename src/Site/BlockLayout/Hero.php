<?php
namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Form\HeroForm;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\Form\FormElementManager\FormElementManagerV3Polyfill as FormElementManager;
use Zend\View\Renderer\PhpRenderer;

class Hero extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Hero'; // @translate
    }

    protected $blockForm = HeroForm::class;

    /**
     * @var FormElementManager
     */
    protected $formElementManager;

    /**
     * @var array
     */
    protected $defaultSettings = [];

    public function prepareForm(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/asset-form.css', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/asset-form.js', 'Omeka'));
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $this->formElementManager = $services->get('FormElementManager');
        $this->defaultSettings = $services->get('Config')['blockplus']['block_settings']['hero'];

        $data = $block ? $block->data() + $this->defaultSettings : $this->defaultSettings;
        $form = $this->formElementManager->get($this->blockForm);
        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }
        $form->setData($dataForm);
        $form->prepare();

        $html = '<p class="explanation">'
            . sprintf($view->translate('Important: currently, the html of the block uses the classes of %sBootstrap%s.'), '<a href="https://getbootstrap.com" target="_blank">', '</a>')
            . ' ' . $view->translate('If it is not enabled in your theme, you should adapt the html.')
            . '</p>';
        return $html . $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $asset = $block->dataValue('asset');
        if ($asset) {
            try {
                $asset = $view->api()->read('assets', $asset)->getContent();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                $asset = null;
            }
        }

        $text = $block->dataValue('text');
        if ($text) {
            $button = $block->dataValue('button');
            if ($button) {
                $url = $block->dataValue('url');
                // No button.
                if (empty($url)) {
                    $button = null;
                }
                // Root of Omeka (that may be in a sub-directory).
                elseif (strpos($url, '/') === 0) {
                    $url = $view->basePath() . $url;
                }
                // Canonical url.
                elseif (strpos($url, 'https://') === 0 || strpos($url, 'http://') === 0) {
                    // Nothing to do.
                }
                // Root of the current site.
                else {
                    $url = $block->page()->site()->url() . '/' . $url;
                }
            } else {
                $url = null;
            }
        } else {
            $button = null;
            $url = null;
        }

        return $view->partial('common/block-layout/hero', [
            'asset' => $asset,
            'text' => $text,
            'button' => $button,
            'url' => $url,
        ]);
    }
}
