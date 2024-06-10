<?php declare(strict_types=1);

namespace BlockPlus\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class CaptionClassAndUrl extends AbstractHelper
{
    /**
     * Extract the first lines of a string to get the optional class and url.
     *
     * The optional class and url may be set at start of each caption like:
     * ```
     * url = https://example.org/
     * class = xxx yyy
     * Next lines are the true caption.
     * ```
     *
     * The initial caption may be an html one.
     *
     * @return array A simple array containing caption, class, url and a flag
     * for html. Get result like:
     * ```php
     * [$caption, $class, $url, $isHtml] = $this->captionClassAndUrl($string);
     * ```
     */
    public function __invoke(?string $string): array
    {
        $string = trim((string) $string);
        if (!$string) {
            return [$string, '', '', false];
        }

        $isHtml = $this->isHtml($string);
        $string = strip_tags($string);

        $url = '';
        $class = '';
        $hasUrl = false;
        $hasClass = false;

        $lines = array_filter(array_map('trim', explode("\n", $string)), 'strlen');
        $matches = [];

        $patternUrl = '~^url\s*=\s*(?<url>[^\s]+)$~';
        $patternClass = '~^class\s*=\s*(?<class>.+)$~';
        if (preg_match($patternClass, $lines[0], $matches)) {
            $class = $matches['class'];
            $hasClass = true;
            unset($lines[0]);
        } elseif (preg_match($patternUrl, $lines[0], $matches)) {
            $url = $matches['url'];
            $hasUrl = true;
            unset($lines[0]);
        }

        if (isset($lines[1]) && ($hasClass || $hasUrl)) {
            if (!$hasUrl && preg_match($patternUrl, $lines[1], $matches)) {
                $url = $matches['url'];
                $hasUrl = true;
                unset($lines[1]);
            } elseif (!$hasClass && preg_match($patternClass, $lines[1], $matches)) {
                $class = $matches['class'];
                $hasClass = true;
                unset($lines[1]);
            }
        }

        if ($hasClass || $hasUrl) {
            $string = implode("\n", $lines);
        }

        if ($isHtml) {
            if ($hasClass) {
                $quoted = preg_quote(trim($class));
                $regex = '~(?:<p>class\s*=\s*' . $quoted . '\s*</p>|<div>class\s*=\s*' . $quoted . '\s*</div>)|class\s*=\s*' . $quoted . '~sU';
                $string = preg_replace($regex, '', $string, 1);
            }
            if ($hasUrl) {
                $quoted = preg_quote($url);
                $regex = '~(?:<p>url\s*=\s*' . $quoted . '\s*</p>|<div>url\s*=\s*' . $quoted . '\s*</div>)|url\s*=\s*' . $quoted . '~sU';
                $string = preg_replace($regex, '', $string, 1);
            }
        } elseif ($hasClass || $hasUrl) {
            $string = implode("\n", $lines);
        }

        return [$string, $class, $url, $isHtml];
    }

    /**
     * Detect if a trimmed string is html or not.
     */
    protected function isHtml(string $string): bool
    {
        return mb_substr($string, 0, 1) === '<'
            && mb_substr($string, -1) === '>'
            && $string !== strip_tags($string);
    }
}
