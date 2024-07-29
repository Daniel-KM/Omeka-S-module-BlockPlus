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

        $lines = array_values(array_filter(array_map('trim', explode("\n", strip_tags($string))), 'strlen'));
        $matches = [];

        // TODO Replace by a one pattern managing any order.
        $patternUrl = '~^url\s*=\s*(?<url>[^\s]+)$~';
        $patternClass = '~^class\s*=\s*(?<class>.+)$~';
        $patternLabel = '~^label\s*=\s*(?<label>.+)$~';
        if (preg_match($patternClass, $lines[0], $matches)) {
            $class = $matches['class'];
            $hasClass = true;
            unset($lines[0]);
        } elseif (preg_match($patternUrl, $lines[0], $matches)) {
            $url = $matches['url'];
            $hasUrl = true;
            unset($lines[0]);
        } elseif (preg_match($patternLabel, $lines[0], $matches)) {
            $label = $matches['label'];
            $hasLabel = true;
            unset($lines[0]);
        }

        if (isset($lines[1]) && ($hasClass || $hasUrl || $hasLabel)) {
            if (!$hasUrl && preg_match($patternUrl, $lines[1], $matches)) {
                $url = $matches['url'];
                $hasUrl = true;
                unset($lines[1]);
            } elseif (!$hasLabel && preg_match($patternLabel, $lines[1], $matches)) {
                $label = $matches['label'];
                $hasLabel = true;
                unset($lines[1]);
            } elseif (!$hasClass && preg_match($patternClass, $lines[1], $matches)) {
                $class = $matches['class'];
                $hasClass = true;
                unset($lines[1]);
            }

            if (isset($lines[2]) && ($hasClass || $hasUrl || $hasLabel)) {
                if (!$hasLabel && preg_match($patternLabel, $lines[1], $matches)) {
                    $label = $matches['label'];
                    $hasLabel = true;
                    unset($lines[2]);
                } elseif (!$hasUrl && preg_match($patternUrl, $lines[1], $matches)) {
                    $url = $matches['url'];
                    $hasUrl = true;
                    unset($lines[2]);
                } elseif (!$hasClass && preg_match($patternClass, $lines[1], $matches)) {
                    $class = $matches['class'];
                    $hasClass = true;
                    unset($lines[2]);
                }
            }
        }

        $isLocalUrl =  $url
            && substr($url, 0, 1) === '/';

        $isMediaFile = $isLocalUrl
            && pathinfo($url, PATHINFO_EXTENSION)
            && preg_match('~/files/(?:original|large|medium|square)/~', $url);

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
            if ($hasLabel) {
                $label = html_entity_decode($label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                $quoted = preg_quote($label);
                $regex = '~(?:<p>label\s*=\s*' . $quoted . '\s*</p>|<div>label\s*=\s*' . $quoted . '\s*</div>)|label\s*=\s*' . $quoted . '~sU';
                $string = preg_replace($regex, '', $string, 1);
            }
        } elseif ($hasClass || $hasUrl || $hasLabel) {
            $string = implode("\n", $lines);
        }

        return [$string, $class, $url, $label, $isLocalUrl, $isMediaFile, $isHtml];
    }
}
