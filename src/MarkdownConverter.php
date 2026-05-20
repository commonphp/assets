<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

final class MarkdownConverter
{
    public function convert(string $markdown): string
    {
        return $this->toHtml($markdown);
    }

    public function toHtml(string $markdown): string
    {
        $lines = preg_split('/\R/', trim($markdown)) ?: [];
        $html = [];
        $paragraph = [];
        $list = [];
        $inCode = false;
        $code = [];

        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '```')) {
                if ($inCode) {
                    $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $code), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
                    $code = [];
                    $inCode = false;
                } else {
                    $this->flushParagraph($html, $paragraph);
                    $this->flushList($html, $list);
                    $inCode = true;
                }

                continue;
            }

            if ($inCode) {
                $code[] = $line;
                continue;
            }

            $trimmed = trim($line);

            if ($trimmed === '') {
                $this->flushParagraph($html, $paragraph);
                $this->flushList($html, $list);
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmed, $matches) === 1) {
                $this->flushParagraph($html, $paragraph);
                $this->flushList($html, $list);
                $level = strlen($matches[1]);
                $html[] = '<h' . $level . '>' . $this->inline($matches[2]) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $matches) === 1) {
                $this->flushParagraph($html, $paragraph);
                $list[] = '<li>' . $this->inline($matches[1]) . '</li>';
                continue;
            }

            $this->flushList($html, $list);
            $paragraph[] = $trimmed;
        }

        if ($inCode) {
            $html[] = '<pre><code>' . htmlspecialchars(implode("\n", $code), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
        }

        $this->flushParagraph($html, $paragraph);
        $this->flushList($html, $list);

        return implode("\n", $html);
    }

    private function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;

        return preg_replace('/\*(.+?)\*/', '<em>$1</em>', $escaped) ?? $escaped;
    }

    /**
     * @param list<string> $html
     * @param list<string> $paragraph
     */
    private function flushParagraph(array &$html, array &$paragraph): void
    {
        if ($paragraph === []) {
            return;
        }

        $html[] = '<p>' . $this->inline(implode(' ', $paragraph)) . '</p>';
        $paragraph = [];
    }

    /**
     * @param list<string> $html
     * @param list<string> $list
     */
    private function flushList(array &$html, array &$list): void
    {
        if ($list === []) {
            return;
        }

        $html[] = '<ul>' . implode('', $list) . '</ul>';
        $list = [];
    }

}
