<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

use BlockPlus\Stdlib\TwitterMirror;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Site\BlockLayout\TemplateableBlockLayoutInterface;
use Omeka\Stdlib\ErrorStore;

/**
 * Display the last messages of a Twitter (X) account.
 *
 * The messages are fetched from the rss feed of a mirror site (Nitter), listed
 * in the main settings, and cached in a file, so the mirror is requested once
 * an hour at most. When no mirror is available, the cached messages are used.
 */
class Twitter extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/twitter';

    /**
     * Duration of the cache in seconds.
     */
    const CACHE_TTL = 3600;

    /**
     * Delay in seconds before a new request when no mirror was available.
     */
    const FAILURE_TTL = 600;

    public function getLabel()
    {
        return 'Twitter'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = $block->getData();
        $data = array_intersect_key($data, ['account' => null, 'limit' => null, 'retweet' => null, 'replies' => null]);
        $data['account'] = TwitterMirror::normalizeAccount((string) ($data['account'] ?? ''));
        $block->setData($data);

        if ($data['account'] === '') {
            $errorStore->addError('o:block', 'A valid Twitter account is required to fetch messages.'); // @translate
        }
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        ?SitePageRepresentation $page = null,
        ?SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['blockplus']['block_settings']['twitter'];
        $blockFieldset = \BlockPlus\Form\TwitterFieldset::class;

        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = self::PARTIAL_NAME)
    {
        $services = $block->getServiceLocator();
        $options = [
            'limit' => (int) $block->dataValue('limit', 1),
            'retweet' => (bool) $block->dataValue('retweet', false),
            'replies' => (bool) $block->dataValue('replies', false),
        ];
        $result = $this->messages($services, (string) $block->dataValue('account', ''), $options);
        if (!$result) {
            $services->get('Omeka\Logger')->warn(
                'The twitter block for page "{page_slug}" in site "{site_slug}" cannot get messages from any mirror.', // @translate
                ['page_slug' => $block->page()->slug(), 'site_slug' => $block->page()->site()->slug()]
            );
            return '';
        }

        return $view->partial($templateViewScript, [
            'block' => $block,
            'account' => $result['account'],
            'messages' => $result['messages'],
        ]);
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // The messages change, so they are not indexed.
        return '';
    }

    /**
     * Get the account and messages from the cache or from a mirror.
     */
    public function messages(ServiceLocatorInterface $services, string $account, array $options): ?array
    {
        $account = TwitterMirror::normalizeAccount($account);
        if ($account === '') {
            return null;
        }

        $cacheFile = $this->cacheFile($services, $account, $options);
        $cache = is_readable($cacheFile)
            ? json_decode((string) file_get_contents($cacheFile), true)
            : null;
        $hasMessages = is_array($cache) && isset($cache['messages']);
        if (is_array($cache)
            && (($cache['fetched'] ?? 0) + self::CACHE_TTL > time()
                || ($cache['failed'] ?? 0) + self::FAILURE_TTL > time())
        ) {
            return $hasMessages ? $cache : null;
        }

        $mirrors = $services->get('Omeka\Settings')->get('blockplus_twitter_mirrors')
            ?: $services->get('Config')['blockplus']['settings']['blockplus_twitter_mirrors'];
        $result = (new TwitterMirror($services->get('Omeka\HttpClient')))->fetch($account, (array) $mirrors, $options);
        if (!$result) {
            // Store the failure, so the mirrors are not requested for each
            // display, and keep the previous messages.
            $cache = (is_array($cache) ? $cache : []);
            $cache['failed'] = time();
            $this->writeCache($cacheFile, $cache);
            return $hasMessages ? $cache : null;
        }

        $result['fetched'] = time();
        $this->writeCache($cacheFile, $result);
        return $result;
    }

    protected function cacheFile(ServiceLocatorInterface $services, string $account, array $options): string
    {
        $basePath = $services->get('Config')['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');
        return $basePath . '/cache/blockplus/twitter-' . mb_strtolower($account) . '-' . md5(serialize($options)) . '.json';
    }

    /**
     * Write the cache atomically, so a reader never gets a partial file.
     */
    protected function writeCache(string $cacheFile, array $result): void
    {
        $dir = dirname($cacheFile);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }
        $tempFile = $cacheFile . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tempFile, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false
            || !@rename($tempFile, $cacheFile)
        ) {
            @unlink($tempFile);
        }
    }
}
