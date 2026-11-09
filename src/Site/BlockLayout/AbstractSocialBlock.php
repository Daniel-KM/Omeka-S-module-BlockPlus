<?php declare(strict_types=1);

namespace BlockPlus\Site\BlockLayout;

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
 * Display the last messages of an account on a social network.
 *
 * The messages are cached in a file, so the network is requested once an hour
 * at most. When it is not available, the cached messages are used and the
 * network is requested again ten minutes later only, so the display of the page
 * is not slowed down.
 */
abstract class AbstractSocialBlock extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    /**
     * Duration of the cache in seconds.
     */
    const CACHE_TTL = 3600;

    /**
     * Delay in seconds before a new request when the network was unavailable.
     */
    const FAILURE_TTL = 600;

    /**
     * The name of the network, used for the block settings and the cache.
     */
    const NETWORK = '';

    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = '';

    /**
     * The fieldset of the block.
     */
    const FIELDSET = '';

    /**
     * Keys of the block data.
     */
    const DATA_KEYS = ['account', 'limit', 'reposts', 'replies'];

    /**
     * Get the identifier of an account from a name, a handle or an url.
     */
    abstract protected function normalizeAccount(string $account): string;

    /**
     * Fetch the account and its last messages from the network.
     *
     * @see \BlockPlus\Stdlib\AbstractSocialFeed
     */
    abstract protected function fetch(ServiceLocatorInterface $services, string $account, array $options): ?array;

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore): void
    {
        $data = array_intersect_key($block->getData(), array_flip(static::DATA_KEYS));
        $data['account'] = $this->normalizeAccount((string) ($data['account'] ?? ''));
        $block->setData($data);

        if ($data['account'] === '') {
            $errorStore->addError('o:block', 'A valid account is required to fetch messages.'); // @translate
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
        $defaultSettings = $services->get('Config')['blockplus']['block_settings'][static::NETWORK];
        $data = $block ? ($block->data() ?? []) + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        $fieldset = $services->get('FormElementManager')->get(static::FIELDSET);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = null)
    {
        $services = $block->getServiceLocator();
        $result = $this->messages($services, (string) $block->dataValue('account', ''), $this->options($block));
        if (!$result) {
            $services->get('Omeka\Logger')->warn(
                'The block {network} for page "{page_slug}" in site "{site_slug}" cannot get messages.', // @translate
                ['network' => static::NETWORK, 'page_slug' => $block->page()->slug(), 'site_slug' => $block->page()->site()->slug()]
            );
            return '';
        }

        return $view->partial($templateViewScript ?: static::PARTIAL_NAME, [
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
     * Get the account and messages from the cache or from the network.
     */
    public function messages(ServiceLocatorInterface $services, string $account, array $options): ?array
    {
        $account = $this->normalizeAccount($account);
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

        $result = $this->fetch($services, $account, $options);
        if (!$result) {
            // Store the failure, so the network is not requested for each
            // display, and keep the previous messages.
            $cache = is_array($cache) ? $cache : [];
            $cache['failed'] = time();
            $this->writeCache($cacheFile, $cache);
            return $hasMessages ? $cache : null;
        }

        $result['fetched'] = time();
        $this->writeCache($cacheFile, $result);
        return $result;
    }

    protected function options(SitePageBlockRepresentation $block): array
    {
        return [
            'limit' => (int) $block->dataValue('limit', 1),
            'reposts' => (bool) $block->dataValue('reposts', false),
            'replies' => (bool) $block->dataValue('replies', false),
        ];
    }

    protected function cacheFile(ServiceLocatorInterface $services, string $account, array $options): string
    {
        $basePath = $services->get('Config')['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');
        return $basePath . '/cache/blockplus/' . static::NETWORK . '-' . md5(mb_strtolower($account) . serialize($options)) . '.json';
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
