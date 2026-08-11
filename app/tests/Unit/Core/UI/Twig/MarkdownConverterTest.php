<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\UI\Twig;

use App\Core\UI\Twig\MarkdownConverter;
use PHPUnit\Framework\TestCase;

class MarkdownConverterTest extends TestCase
{
    private MarkdownConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new MarkdownConverter();
    }

    public function testConvertsBold(): void
    {
        $html = $this->converter->toHtml('Hello **world**');
        $this->assertStringContainsString('<strong>world</strong>', $html);
    }

    public function testConvertsItalic(): void
    {
        $html = $this->converter->toHtml('Hello *world*');
        $this->assertStringContainsString('<em>world</em>', $html);
    }

    public function testConvertsCode(): void
    {
        $html = $this->converter->toHtml('Use `function()`');
        $this->assertStringContainsString('<code>function()</code>', $html);
    }

    public function testConvertsLink(): void
    {
        $html = $this->converter->toHtml('[Click](https://example.com)');
        $this->assertStringContainsString('<a href="https://example.com">Click</a>', $html);
    }

    public function testConvertsUnorderedList(): void
    {
        $html = $this->converter->toHtml("- Item 1\n- Item 2");
        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
    }

    public function testConvertsOrderedList(): void
    {
        $html = $this->converter->toHtml("1. First\n2. Second");
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>First</li>', $html);
    }

    public function testConvertsHeading(): void
    {
        $html = $this->converter->toHtml('# Title');
        $this->assertStringContainsString('<h1>Title</h1>', $html);
    }

    public function testConvertsBlockquote(): void
    {
        $html = $this->converter->toHtml('> Quote');
        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('Quote', $html);
    }

    public function testConvertsCodeBlock(): void
    {
        $html = $this->converter->toHtml("```php\necho 'hi';\n```");
        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('echo ', $html);
    }

    public function testConvertsHorizontalRule(): void
    {
        $html = $this->converter->toHtml('---');
        $this->assertStringContainsString('<hr', $html);
    }

    public function testStripsScriptTags(): void
    {
        $html = $this->converter->toHtml('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert', $html);
    }

    public function testStripsDangerousAttributes(): void
    {
        $html = $this->converter->toHtml('<p onclick="alert(1)">Hi</p>');
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function testStripsImgOnerror(): void
    {
        $html = $this->converter->toHtml('<img src="x" onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function testReturnsEmptyStringForNull(): void
    {
        $this->assertSame('', $this->converter->toHtml(null));
    }

    public function testReturnsEmptyStringForEmptyString(): void
    {
        $this->assertSame('', $this->converter->toHtml(''));
    }

    public function testParagraphWrapping(): void
    {
        $html = $this->converter->toHtml('Hello');
        $this->assertStringContainsString('<p>Hello</p>', $html);
    }

    public function testPreservesSingleLineBreaks(): void
    {
        $html = $this->converter->toHtml("Line one\nLine two");
        $this->assertStringContainsString("Line one<br>\nLine two", $html);
    }
}
