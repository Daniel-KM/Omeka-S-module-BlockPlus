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
     * @return array A simple array containing caption, class and url.
     */
    public function __invoke(?string $string): array
    {
        $string = trim((string) $string);
        if (!$string) {
            return [$string, '', ''];
        }

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

        return [$string, $class, $url];
    }
}
