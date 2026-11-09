<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class CaptionClassAndUrl extends AbstractHelper
{
    /**
     * Extract the first lines of a string to get class, url and label if any.
     *
     * The optional class, url and label may be set at start of a string like:
     * ```
     * url = https://example.org/
     * class = xxx yyy
     * label = Any zzz
     * Next lines are the true caption, that may be raw text ot html.
     * ```
     *
     * The string may be html.
     * The url may be a local media file, for example `/files/original/xxx.pdf`.
     *
     * @return array A simple array containing caption, class, url, label, and
     * flags for local url (relative url), local media file, and html.
     * Get result like:
     * ```php
     * [$caption, $class, $url, $label, $isLocalUrl, $isMediaFile, $isHtml] = $this->captionClassAndUrl($string);
     * ```
     */
    public function __invoke(?string $string): array
    {
        $string = trim((string) $string);
        if (!$string) {
            return [$string, '', '', '', false, false, false];
        }

        $isHtml = $this->getView()->isHtml($string);

        $url = '';
        $class = '';
        $label = '';
        $hasUrl = false;
        $hasClass = false;
        $hasLabel = false;

        // The html block elements are separated by a new line before removing
        // the tags, else the lines of a html without new lines are merged.
        $text = $isHtml
            ? preg_replace('~(</(?:p|div|h[1-6]|li)>|<br\s*/?>)~i', "$1\n", $string)
            : $string;
        $lines = array_values(array_filter(array_map('trim', explode("\n", strip_tags($text))), 'strlen'));

        // Single pattern matching class, url, or label in any order (first 3 lines max).
        $pattern = '~^(?<key>url|class|label)\s*=\s*(?<value>.+)$~';
        $maxLines = min(3, count($lines));
        for ($i = 0; $i < $maxLines; $i++) {
            if (!isset($lines[$i])) {
                break;
            }
            $matches = [];
            if (preg_match($pattern, $lines[$i], $matches)) {
                $key = $matches['key'];
                $value = $key === 'url' ? trim($matches['value']) : $matches['value'];
                // Only set if not already set.
                if ($key === 'url' && !$hasUrl) {
                    $url = $value;
                    $hasUrl = true;
                    unset($lines[$i]);
                } elseif ($key === 'class' && !$hasClass) {
                    $class = $value;
                    $hasClass = true;
                    unset($lines[$i]);
                } elseif ($key === 'label' && !$hasLabel) {
                    $label = $value;
                    $hasLabel = true;
                    unset($lines[$i]);
                }
            } else {
                // Stop at first non-matching line.
                break;
            }
        }

        $isLocalUrl = $url
            && substr($url, 0, 1) === '/';

        $isMediaFile = $isLocalUrl
            && pathinfo($url, PATHINFO_EXTENSION)
            && preg_match('~/files/(?:original|large|medium|square)/~', $url);

        if ($isHtml) {
            if ($hasClass) {
                $quoted = preg_quote(trim($class), '~');
                $regex = '~(?:<p>class\s*=\s*' . $quoted . '\s*</p>|<div>class\s*=\s*' . $quoted . '\s*</div>)|class\s*=\s*' . $quoted . '~sU';
                $string = preg_replace($regex, '', $string, 1);
            }
            if ($hasUrl) {
                $quoted = preg_quote($url, '~');
                $regex = '~(?:<p>url\s*=\s*' . $quoted . '\s*</p>|<div>url\s*=\s*' . $quoted . '\s*</div>)|url\s*=\s*' . $quoted . '~sU';
                $string = preg_replace($regex, '', $string, 1);
            }
            if ($hasLabel) {
                // The label is searched as written in the html, then decoded.
                $quoted = preg_quote($label, '~');
                $label = html_entity_decode($label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                $regex = '~(?:<p>label\s*=\s*' . $quoted . '\s*</p>|<div>label\s*=\s*' . $quoted . '\s*</div>)|label\s*=\s*' . $quoted . '~sU';
                $string = preg_replace($regex, '', $string, 1);
            }
        } elseif ($hasClass || $hasUrl || $hasLabel) {
            $string = implode("\n", $lines);
        }

        return [$string, $class, $url, $label, $isLocalUrl, $isMediaFile, $isHtml];
    }
}
