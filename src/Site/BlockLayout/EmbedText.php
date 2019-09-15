<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\File\Downloader;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\HtmlPurifier;
use Zend\Dom\Query;
use Zend\Http\Client as HttpClient;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

/**
 * Allow to display an external asset that is not a resource or an asset file.
 *
 * @link https://omeka.org/s/docs/user-manual/sites/site_pages/#media
 */
class EmbedText extends AbstractBlockLayout
{
    use CommonTrait;

    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    /**
     * @var array
     */
    protected $whitelist;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var Downloader
     */
    protected $downloader;

    public function __construct(
        HtmlPurifier $htmlPurifier,
        array $whitelist,
        HttpClient $httpClient,
        Downloader $downloader
    ) {
        $this->htmlPurifier = $htmlPurifier;
        $this->whitelist = $whitelist;
        $this->httpClient = $httpClient;
        $this->downloader = $downloader;
    }

    public function getLabel()
    {
        return 'Embedded with text'; // @translate
    }

    public function prepareRender(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/block-plus.css', 'BlockPlus'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        /**
         * @see \Omeka\Media\Ingester\OEmbed
         * @see \Omeka\Site\BlockLayout\Html
         */
        $data = $block->getData();

        // Currently, the UI manages only one embed, but it simplifies future
        // improvments
        $embed = $data['embeds'];
        if ($embed) {
            $whitelisted = false;
            foreach ($this->whitelist as $regex) {
                if (preg_match($regex, $embed) === 1) {
                    $whitelisted = true;
                    break;
                }
            }

            if (!$whitelisted) {
                $errorStore->addError('embeds', 'Invalid OEmbed URL'); // @translate
                return;
            }

            $source = $embed;

            $response = $this->makeRequest($source, 'OEmbed URL', $errorStore); // @translate
            if (!$response) {
                $errorStore->addError('embeds', 'OEmbed URL unavailable'); // @translate
                return;
            }

            $document = $response->getBody();
            $dom = new Query($document);
            $oEmbedLinks = $dom->queryXpath('//link[@rel="alternate" or @rel="alternative"][@type="application/json+oembed"]');
            if (!count($oEmbedLinks)) {
                $errorStore->addError('embeds', 'No OEmbed links were found at the given URI'); // @translate
                return;
            }

            $oEmbedLink = $oEmbedLinks[0];
            $linkResponse = $this->makeRequest($oEmbedLink->getAttribute('href'),
                'OEmbed link URL', $errorStore); // @translate
            if (!$linkResponse) {
                $errorStore->addError('embeds', 'OEmbed link URL unavailable'); // @translate
                return;
            }

            $mediaData = json_decode($linkResponse->getBody(), true);
            if (!$mediaData) {
                $errorStore->addError('embeds', 'Error decoding OEmbed JSON'); // @translate
                return;
            }

            $data['embeds'] = [
                [
                    'source' => $embed,
                    'data' => $mediaData,
                ],
            ];
        }

        $data['html'] = isset($data['html'])
            ? $this->fixEndOfLine($this->htmlPurifier->purify($data['html']))
            : '';

        $block->setData($data);
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['embedText'];
        $blockFieldset = \BlockPlus\Form\EmbedTextFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        // TODO Manage multiple embedded resources with caption like media text.
        $data['embeds'] = empty($data['embeds'][0]['source'])
            ? ''
            : $data['embeds'][0]['source'];

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $embeds = $block->dataValue('embeds', []);
        $html = $block->dataValue('html', '');
        if (!$embeds && !$html) {
            return '';
        }

        $template = $block->dataValue('template') ?: 'common/block-layout/embed-text';

        return $view->partial($template, [
            'block' => $block,
            'heading' => $block->dataValue('heading', ''),
            'embeds' => $embeds,
            'html' => $html,
            'alignmentClass' => $block->dataValue('alignment', 'left'),
            'showTitleOption' => $block->dataValue('show_title_option', 'item_title'),
            'captionPosition' => $block->dataValue('caption_position', 'center'),
            'linkText' => $block->dataValue('link_text', ''),
            'linkUrl' => $block->dataValue('link_url', ''),
        ]);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // TODO Add captions (they are not added in the core)?
        return $block->dataValue('heading', '')
            . ' ' . $block->dataValue('html', '');
    }

    /**
     * Make a request and handle any errors that might occur.
     *
     * @param string $url URL to request
     * @param string $type Type of URL (used to compose error messages)
     * @param ErrorStore $errorStore
     */
    protected function makeRequest($url, $type, ErrorStore $errorStore)
    {
        $uri = new HttpUri($url);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('embed', sprintf('Invalid "%s" specified', $type)); // @translate
            return false;
        }

        $client = $this->httpClient;
        $client->reset();
        $client->setUri($uri);
        $response = $client->send();

        if (!$response->isOk()) {
            $errorStore->addError('embed', sprintf(
                "Error reading %s: %s (%s)", // @translate
                $type,
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return false;
        }

        return $response;
    }
}
