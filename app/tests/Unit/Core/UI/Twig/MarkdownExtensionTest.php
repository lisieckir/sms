<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\UI\Twig;

use App\Core\UI\Twig\MarkdownConverter;
use App\Core\UI\Twig\MarkdownExtension;
use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtensionTest extends TestCase
{
    private MarkdownExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new MarkdownExtension(new MarkdownConverter());
    }

    public function testIsTwigExtension(): void
    {
        $this->assertInstanceOf(AbstractExtension::class, $this->extension);
    }

    public function testRegistersMarkdownFilter(): void
    {
        $filters = $this->extension->getFilters();
        $this->assertCount(1, $filters);

        $filter = $filters[0];
        $this->assertInstanceOf(TwigFilter::class, $filter);
        $this->assertSame('markdown', $filter->getName());
    }

    public function testMarkdownFilterConvertsToHtml(): void
    {
        $html = $this->extension->markdownToHtml('Hello **world**');
        $this->assertStringContainsString('<strong>world</strong>', $html);
    }

    public function testMarkdownFilterHandlesNull(): void
    {
        $html = $this->extension->markdownToHtml(null);
        $this->assertSame('', $html);
    }

    public function testMarkdownFilterHandlesEmptyString(): void
    {
        $html = $this->extension->markdownToHtml('');
        $this->assertSame('', $html);
    }

    public function testMarkdownFilterStripsXss(): void
    {
        $html = $this->extension->markdownToHtml('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $html);
    }
}
