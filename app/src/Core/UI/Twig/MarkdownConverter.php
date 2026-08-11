<?php

declare(strict_types=1);

namespace App\Core\UI\Twig;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class MarkdownConverter
{
    private CommonMarkConverter $converter;
    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        // Keep intentional line breaks from textareas when rendering Markdown.
        $this->converter = new CommonMarkConverter([
            'renderer' => [
                'soft_break' => "<br>\n",
            ],
        ]);

        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('a', ['href'])
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('h5')
            ->allowElement('h6')
            ->allowElement('blockquote')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('hr');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function toHtml(?string $markdown): string
    {
        if ($markdown === null || $markdown === '') {
            return '';
        }

        try {
            $html = $this->converter->convert($markdown)->getContent();
        } catch (CommonMarkException) {
            return '';
        }

        return $this->sanitizer->sanitize($html);
    }
}
