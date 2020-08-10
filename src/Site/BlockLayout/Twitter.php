<?php
namespace BlockPlus\Site\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;
use Zend\View\Renderer\PhpRenderer;

class Twitter extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/twitter';

    public function getLabel()
    {
        return 'Twitter'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $account = isset($data['account']) ? $data['account'] : '';
        $messages = $this->fetchMessages($account);
        if ($account && !count($messages)) {
            $messenger = new Messenger;
            $messenger->addWarning(new Message('The Twitter account "%s" has no message currently.', $account)); // @translate
        }
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['twitter'];
        $blockFieldset = \BlockPlus\Form\TwitterFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $vars = $block->data();
        unset($vars['template']);
        $vars['messages'] = $this->fetchMessages($vars['account']);
        $template = $block->dataValue('template', self::PARTIAL_NAME);
        return $view->resolver($template)
            ? $view->partial($template, $vars)
            : $view->partial(self::PARTIAL_NAME, $vars);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // The twitter message cannot be searched, because it cannot be updated.
        return $block->dataValue('title', '');
    }

    protected function fetchMessages($account)
    {
        if (empty($account)) {
            return [];
        }

        // The process fetched directly the web page in order to avoid to add a
        // specific package and to avoid to create credential keys in Twitter.
        // It is possible only with the mobile page, that doesn't use ajax.
        $url =  'https://mobile.twitter.com/' . $account;
        $html = file_get_contents($url);
        if (empty($html)) {
            return [];
        }

        $result = [];

        // The links are rebuild for the main site.
        $baseUrl = 'https://twitter.com';

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html, LIBXML_BIGLINES | LIBXML_COMPACT | LIBXML_NOENT | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NSCLEAN | LIBXML_NOCDATA);
        $doc->preserveWhiteSpace = false;
        $xpath = new \DOMXPath($doc);

        $result = [];
        $query = '//div[@class="timeline"]/table[normalize-space(@class)="tweet"]';
        $nodeList = $xpath->query($query);
        if (!$nodeList || !$nodeList->length) {
            return [];
        }

        foreach ($nodeList as $key => $node) {
            $text = $xpath->query('//div[@class="timeline"]/table[normalize-space(@class)="tweet"]'
                . '[' . ($key + 1) . ']'
                . '//div[@class="tweet-text"]');
            if (!$text->count()) {
                continue;
            }

            // Use absolute urls.
            // TODO Use the dom to create external link!
            $content = str_replace(
                [' href="/', '<a '],
                [' href="' . $baseUrl . '/', '<a rel="nofollow noopener" target="_blank" '],
                $text->item(0)->C14N()
            );

            $id = $xpath->query('//div[@class="timeline"]/table[normalize-space(@class)="tweet"]'
                . '[' . ($key + 1) . ']'
                . '//div[@class="tweet-text"]/@data-id')->item(0)->nodeValue;

            // The simplest process is to convert the xml into an array.
            $simpleXmlNode = simplexml_import_dom($node);
            $simple = json_decode(json_encode($simpleXmlNode), true);

            // Manage retweet.
            if (isset($simple['tr'][0]['td'][1]['span']) && strpos($simple['tr'][0]['td'][1]['span'], 'retweeted') !== false) {
                $content = [
                    'node' => $node,
                    'context' => 'retweet',
                    'account' => [
                        'fullname' => $simple['tr'][1]['td'][0]['a']['img']['@attributes']['alt'],
                        'url' => $baseUrl . $simple['tr'][1]['td'][0]['a']['@attributes']['href'],
                        'avatar' => [
                            'url' => $baseUrl . $simple['tr'][1]['td'][0]['a']['@attributes']['href'],
                            'img' => $simple['tr'][1]['td'][0]['a']['img']['@attributes']['src'],
                        ],
                    ],
                    'id' => $id,
                    'url' => $baseUrl . $simple['@attributes']['href'],
                    'timestamp' => $simple['tr'][1]['td'][2]['a'],
                    'content' => $content,
                ];
            } else {
                $content = [
                    'node' => $node,
                    'context' => null,
                    'account' => [
                        'fullname' => $simple['tr'][0]['td'][0]['a']['img']['@attributes']['alt'],
                        'url' => $baseUrl . $simple['tr'][0]['td'][0]['a']['@attributes']['href'],
                        'avatar' => [
                            'url' => $baseUrl . $simple['tr'][0]['td'][0]['a']['@attributes']['href'],
                            'img' => $simple['tr'][0]['td'][0]['a']['img']['@attributes']['src'],
                        ],
                    ],
                    'id' => $id,
                    'url' => $baseUrl . $simple['@attributes']['href'],
                    'timestamp' => $simple['tr'][0]['td'][2]['a'],
                    'content' => $content,
                ];
            }
            $result[] = $content;
        }

        return $result;
    }
}
