<?php

declare(strict_types=1);

namespace App\Core\UI\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class MarkdownExtension extends AbstractExtension
{
    public function __construct(
        private readonly MarkdownConverter $markdownConverter,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', $this->markdownToHtml(...), ['is_safe' => ['html']]),
        ];
    }

    public function markdownToHtml(?string $text): string
    {
        return $this->markdownConverter->toHtml($text);
    }
}
