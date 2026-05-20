<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Enums;

enum AssetType: string
{
    case Stylesheet = 'stylesheet';
    case Script = 'script';
    case Image = 'image';
    case Font = 'font';
    case Document = 'document';
    case Data = 'data';
    case Text = 'text';
    case Media = 'media';
    case Other = 'other';

    public static function fromPath(string $path, ?string $mimeType = null): self
    {
        if ($mimeType !== null && trim($mimeType) !== '') {
            return self::fromMimeType($mimeType);
        }

        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'css', 'scss', 'sass', 'less' => self::Stylesheet,
            'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx' => self::Script,
            'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'bmp', 'ico', 'svg' => self::Image,
            'woff', 'woff2', 'ttf', 'otf', 'eot' => self::Font,
            'html', 'htm', 'pdf' => self::Document,
            'json', 'xml', 'yaml', 'yml', 'toml', 'map' => self::Data,
            'mp3', 'wav', 'ogg', 'mp4', 'webm', 'mov' => self::Media,
            'txt', 'md', 'csv' => self::Text,
            default => self::Other,
        };
    }

    public static function fromMimeType(string $mimeType): self
    {
        $mimeType = strtolower(trim(strstr($mimeType, ';', true) ?: $mimeType));

        if ($mimeType === 'text/css') {
            return self::Stylesheet;
        }

        if (in_array($mimeType, ['text/javascript', 'application/javascript', 'application/ecmascript'], true)) {
            return self::Script;
        }

        if (str_starts_with($mimeType, 'image/')) {
            return self::Image;
        }

        if (str_starts_with($mimeType, 'font/') || in_array($mimeType, [
            'application/font-woff',
            'application/vnd.ms-fontobject',
            'application/x-font-ttf',
            'application/x-font-opentype',
        ], true)) {
            return self::Font;
        }

        if (str_starts_with($mimeType, 'audio/') || str_starts_with($mimeType, 'video/')) {
            return self::Media;
        }

        if (in_array($mimeType, ['text/html', 'application/pdf'], true)) {
            return self::Document;
        }

        if (
            str_starts_with($mimeType, 'text/')
            || in_array($mimeType, ['application/xml', 'application/xhtml+xml'], true)
        ) {
            return self::Text;
        }

        if (
            str_contains($mimeType, '+json')
            || str_contains($mimeType, '+xml')
            || in_array($mimeType, ['application/json', 'application/wasm', 'application/octet-stream'], true)
        ) {
            return self::Data;
        }

        return self::Other;
    }

    public function isTextual(): bool
    {
        return in_array($this, [self::Stylesheet, self::Script, self::Document, self::Data, self::Text], true);
    }

    public function isBinary(): bool
    {
        return !$this->isTextual();
    }
}
