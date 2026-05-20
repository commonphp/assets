<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Enums\AssetType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetTypeTest extends TestCase
{
    #[DataProvider('pathTypeProvider')]
    public function testItClassifiesPaths(string $path, AssetType $type): void
    {
        self::assertSame($type, AssetType::fromPath($path));
    }

    #[DataProvider('mimeTypeProvider')]
    public function testItClassifiesMimeTypes(string $mimeType, AssetType $type): void
    {
        self::assertSame($type, AssetType::fromMimeType($mimeType));
        self::assertSame($type, AssetType::fromPath('asset.bin', $mimeType));
    }

    public function testItReportsTextualAndBinaryTypes(): void
    {
        self::assertTrue(AssetType::Stylesheet->isTextual());
        self::assertTrue(AssetType::Script->isTextual());
        self::assertTrue(AssetType::Document->isTextual());
        self::assertTrue(AssetType::Data->isTextual());
        self::assertTrue(AssetType::Text->isTextual());
        self::assertFalse(AssetType::Image->isTextual());
        self::assertTrue(AssetType::Font->isBinary());
        self::assertTrue(AssetType::Media->isBinary());
    }

    public static function pathTypeProvider(): iterable
    {
        yield 'stylesheet' => ['css/app.css', AssetType::Stylesheet];
        yield 'script' => ['js/app.mjs?version=1', AssetType::Script];
        yield 'image' => ['images/logo.svg', AssetType::Image];
        yield 'font' => ['fonts/inter.woff2', AssetType::Font];
        yield 'document' => ['index.html', AssetType::Document];
        yield 'data' => ['manifest.json', AssetType::Data];
        yield 'media' => ['video.webm', AssetType::Media];
        yield 'text' => ['readme.md', AssetType::Text];
        yield 'other' => ['download.pkg', AssetType::Other];
    }

    public static function mimeTypeProvider(): iterable
    {
        yield 'css' => ['text/css; charset=utf-8', AssetType::Stylesheet];
        yield 'javascript' => ['application/javascript', AssetType::Script];
        yield 'image' => ['image/png', AssetType::Image];
        yield 'font' => ['font/woff2', AssetType::Font];
        yield 'audio' => ['audio/mpeg', AssetType::Media];
        yield 'video' => ['video/mp4', AssetType::Media];
        yield 'html' => ['text/html', AssetType::Document];
        yield 'plain text' => ['text/plain', AssetType::Text];
        yield 'json suffix' => ['application/manifest+json', AssetType::Data];
        yield 'unknown' => ['application/x-custom', AssetType::Other];
    }
}
