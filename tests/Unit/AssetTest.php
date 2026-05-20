<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\Contracts\MimeTypeResolverInterface;
use CommonPHP\Assets\Enums\AssetType;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use CommonPHP\Assets\Tests\Fixtures\TemporaryAssetsTrait;
use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
    use TemporaryAssetsTrait;

    public function testItCreatesMetadataFromAFile(): void
    {
        $root = $this->createTemporaryDirectory();
        $contents = 'body { color: #123; }';
        $file = $this->writeAssetFile($root, 'css/app.css', $contents);
        $mimeTypes = new class implements MimeTypeResolverInterface {
            public function resolve(string $path): string
            {
                return 'text/css; charset=utf-8';
            }
        };

        $asset = Asset::fromFile('css/app.css', $file, $mimeTypes);

        self::assertSame('css/app.css', $asset->path());
        self::assertSame($file, $asset->realPath());
        self::assertSame('app.css', $asset->filename());
        self::assertSame('css', $asset->extension());
        self::assertSame('text/css; charset=utf-8', $asset->mimeType());
        self::assertSame(strlen($contents), $asset->size());
        self::assertSame(filemtime($file), $asset->modifiedAt());
        self::assertSame(AssetType::Stylesheet, $asset->type());
        self::assertMatchesRegularExpression('/^"[a-f0-9]{40}"$/', $asset->etag());
        self::assertSame(gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT', $asset->lastModifiedHeader());
        self::assertSame($contents, $asset->contents());
        self::assertSame('css/app.css', (string) $asset);
    }

    public function testItConvertsToArray(): void
    {
        $root = $this->createTemporaryDirectory();
        $file = $this->writeAssetFile($root, 'images/logo.svg', '<svg></svg>');
        $asset = new Asset('images/logo.svg', $file, 'image/svg+xml', 11, 1234567890, AssetType::Image);

        self::assertSame([
            'path' => 'images/logo.svg',
            'realPath' => $file,
            'filename' => 'logo.svg',
            'extension' => 'svg',
            'mimeType' => 'image/svg+xml',
            'size' => 11,
            'modifiedAt' => 1234567890,
            'type' => 'image',
            'etag' => $asset->etag(),
        ], $asset->toArray());
    }

    public function testExtensionReturnsNullForExtensionlessPaths(): void
    {
        $asset = new Asset('robots', __FILE__, 'text/plain', 0, 1);

        self::assertNull($asset->extension());
    }

    public function testContentsThrowsWhenTheResolvedFileCannotBeRead(): void
    {
        $root = $this->createTemporaryDirectory();
        $file = $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $asset = Asset::fromFile('css/app.css', $file);

        unlink($file);

        $this->expectException(UnreadableAssetException::class);
        $this->expectExceptionMessage('Asset is not readable: "css/app.css".');

        $asset->contents();
    }
}
