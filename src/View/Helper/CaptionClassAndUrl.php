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
     * Next lines are the true caption, that may be raw text ot html.
     * ```
     * The url may be a local media file, for example `/files/original/xxx.pdf`.
     *
     * @return array A simple array containing caption, class, url and flags
     * for html and local media file. Get result like:
     * ```php
     * [$caption, $class, $url, $isHtml, $isMediaFile] = $this->captionClassAndUrl($string);
     * ```
     */
    public function __invoke(?string $string): array
    {
        $string = trim((string) $string);
        if (!$string) {
            return [$string, '', '', false];
        }

        $isHtml = $this->getView()->isHtml($string);

        $url = '';
        $class = '';
        $hasUrl = false;
        $hasClass = false;

        $lines = array_values(array_filter(array_map('trim', explode("\n", strip_tags($string))), 'strlen'));
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

        $isMediaFile = $url
            && substr($url, 0, 1) === '/'
            && pathinfo($url, PATHINFO_EXTENSION)
            && preg_match('~/files/(?:original|large|medium|square)/~', $url);

        return [$string, $class, $url, $isHtml, $isMediaFile];
    }
}
