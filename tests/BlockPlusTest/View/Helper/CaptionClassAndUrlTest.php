<?php declare(strict_types=1);

namespace BlockPlusTest\View\Helper;

use Omeka\Test\AbstractHttpControllerTestCase;

class CaptionClassAndUrlTest extends AbstractHttpControllerTestCase
{
    protected function caption(?string $string): array
    {
        return $this->getApplicationServiceLocator()->get('ViewHelperManager')->get('captionClassAndUrl')($string);
    }

    public function testEmptyString(): void
    {
        $this->assertSame(['', '', '', '', false, false, false], $this->caption(null));
        $this->assertSame(['', '', '', '', false, false, false], $this->caption('  '));
    }

    public function testCaptionWithoutKeys(): void
    {
        $this->assertSame(['A simple caption', '', '', '', false, false, false], $this->caption('A simple caption'));
    }

    /**
     * The three keys are read, whatever the order, even the third one.
     */
    public function testThreeKeysInText(): void
    {
        [$caption, $class, $url, $label, $isLocalUrl, $isMediaFile, $isHtml] = $this->caption(
            "url = https://example.org/page\nclass = button large\nlabel = Read more\nThe true caption\non two lines."
        );
        $this->assertSame("The true caption\non two lines.", $caption);
        $this->assertSame('button large', $class);
        $this->assertSame('https://example.org/page', $url);
        $this->assertSame('Read more', $label);
        $this->assertFalse($isLocalUrl);
        $this->assertFalse($isMediaFile);
        $this->assertFalse($isHtml);
    }

    /**
     * The third key was skipped when the caption had a single line, because
     * the removed lines reduced the count of lines to check.
     */
    public function testThreeKeysWithSingleLineCaption(): void
    {
        [$caption, $class, $url, $label] = $this->caption("url = https://example.org\nclass = button\nlabel = Read more\nCaption");
        $this->assertSame(['Caption', 'button', 'https://example.org', 'Read more'], [$caption, $class, $url, $label]);
    }

    public function testKeysInAnyOrder(): void
    {
        [$caption, $class, $url, $label] = $this->caption("label = Read more\nclass = button\nurl = /s/site/page/about\nCaption");
        $this->assertSame(['Caption', 'button', '/s/site/page/about', 'Read more'], [$caption, $class, $url, $label]);
    }

    public function testKeysAreReadAtStartOnly(): void
    {
        [$caption, $class, $url] = $this->caption("Caption first\nurl = https://example.org");
        $this->assertSame("Caption first\nurl = https://example.org", $caption);
        $this->assertSame('', $class);
        $this->assertSame('', $url);
    }

    public function testLocalMediaFile(): void
    {
        [, , $url, , $isLocalUrl, $isMediaFile] = $this->caption("url = /files/original/abc.pdf\nDocument");
        $this->assertSame('/files/original/abc.pdf', $url);
        $this->assertTrue($isLocalUrl);
        $this->assertTrue($isMediaFile);

        [, , , , $isLocalUrl, $isMediaFile] = $this->caption("url = /s/site/page/about\nPage");
        $this->assertTrue($isLocalUrl);
        $this->assertFalse($isMediaFile);
    }

    public function testKeysInHtml(): void
    {
        [$caption, $class, $url, $label, , , $isHtml] = $this->caption(
            '<p>url = https://example.org/page</p><p>class = button</p><p>label = Read &amp; see</p><p>The <strong>true</strong> caption.</p>'
        );
        $this->assertTrue($isHtml);
        $this->assertSame('https://example.org/page', $url);
        $this->assertSame('button', $class);
        $this->assertSame('Read & see', $label);
        $this->assertSame('<p>The <strong>true</strong> caption.</p>', $caption);
    }

    public function testUrlWithSpecialCharactersInHtml(): void
    {
        [$caption, , $url] = $this->caption('<p>url = https://example.org/~user/page?a=1&amp;b=2</p><p>Caption</p>');
        $this->assertSame('https://example.org/~user/page?a=1&amp;b=2', $url);
        $this->assertSame('<p>Caption</p>', $caption);
    }
}
