<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\MarkdownConverter;
use PHPUnit\Framework\TestCase;

final class MarkdownConverterTest extends TestCase
{
    public function testConvertDelegatesToHtmlConversion(): void
    {
        $converter = new MarkdownConverter();

        self::assertSame($converter->toHtml('# Title'), $converter->convert('# Title'));
    }

    public function testItConvertsHeadingsParagraphsListsAndInlineStyles(): void
    {
        $markdown = <<<'MARKDOWN'
        # Assets

        Serve **static** and *built* files.

        - CSS
        - JavaScript
        MARKDOWN;

        $html = (new MarkdownConverter())->toHtml($markdown);

        self::assertStringContainsString('<h1>Assets</h1>', $html);
        self::assertStringContainsString('<p>Serve <strong>static</strong> and <em>built</em> files.</p>', $html);
        self::assertStringContainsString('<ul><li>CSS</li><li>JavaScript</li></ul>', $html);
    }

    public function testItConvertsFencedCodeAndEscapesHtml(): void
    {
        $markdown = <<<'MARKDOWN'
        ```
        <script>alert("x")</script>
        ```
        MARKDOWN;

        $html = (new MarkdownConverter())->toHtml($markdown);

        self::assertSame(
            '<pre><code>&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;</code></pre>',
            $html,
        );
    }

    public function testUnclosedCodeFenceStillProducesCodeBlock(): void
    {
        $html = (new MarkdownConverter())->toHtml("```\nbody {}");

        self::assertSame('<pre><code>body {}</code></pre>', $html);
    }

    public function testItEscapesParagraphHtml(): void
    {
        $html = (new MarkdownConverter())->toHtml('<strong>not raw</strong>');

        self::assertSame('<p>&lt;strong&gt;not raw&lt;/strong&gt;</p>', $html);
    }
}
