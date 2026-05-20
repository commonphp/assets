<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\AssetManifest;
use CommonPHP\Assets\Contracts\AssetManifestInterface;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use CommonPHP\Assets\Tests\Fixtures\TemporaryAssetsTrait;
use PHPUnit\Framework\TestCase;

final class AssetManifestTest extends TestCase
{
    use TemporaryAssetsTrait;

    public function testItImplementsTheContractAndNormalizesEntries(): void
    {
        $manifest = new AssetManifest([
            '/css/app.css' => '/css/app.123.css',
            'js/app.js' => ['file' => 'js/app.456.js'],
            'remote.js' => 'https://cdn.example.test/remote.js',
        ]);

        self::assertInstanceOf(AssetManifestInterface::class, $manifest);
        self::assertTrue($manifest->has('css/app.css'));
        self::assertSame('css/app.123.css', $manifest->get('css/app.css'));
        self::assertSame('js/app.456.js', $manifest->path('js/app.js'));
        self::assertSame([
            'css/app.css' => 'css/app.123.css',
            'js/app.js' => 'js/app.456.js',
            'remote.js' => 'https://cdn.example.test/remote.js',
        ], $manifest->all());
    }

    public function testUrlUsesManifestTargetsAndFallsBackToTheRequestedPath(): void
    {
        $manifest = new AssetManifest([
            'css/app.css' => 'css/app.123.css',
            'remote.js' => 'https://cdn.example.test/remote.js',
        ]);

        self::assertSame('/assets/css/app.123.css', $manifest->url('css/app.css'));
        self::assertSame('/static/images/logo.svg', $manifest->url('images/logo.svg', '/static'));
        self::assertSame('https://cdn.example.test/remote.js', $manifest->url('remote.js'));
    }

    public function testPathFallsBackToRequestedPathWhenManifestHasNoEntry(): void
    {
        self::assertSame('images/logo.svg', AssetManifest::empty()->path('/images/logo.svg'));
    }

    public function testPathRejectsExternalTargets(): void
    {
        $manifest = new AssetManifest(['remote.js' => 'https://cdn.example.test/remote.js']);

        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('manifest target is an external URL');

        $manifest->path('remote.js');
    }

    public function testItLoadsFromAJsonFile(): void
    {
        $root = $this->createTemporaryDirectory();
        $manifestFile = $this->writeAssetFile($root, 'manifest.json', json_encode([
            'css/app.css' => 'css/app.123.css',
        ], JSON_THROW_ON_ERROR));

        $manifest = AssetManifest::fromFile($manifestFile);

        self::assertSame('css/app.123.css', $manifest->get('css/app.css'));
    }

    public function testMissingManifestFileThrowsUnreadableException(): void
    {
        $this->expectException(UnreadableAssetException::class);

        AssetManifest::fromFile($this->createTemporaryDirectory() . '/missing.json');
    }

    public function testInvalidManifestJsonThrowsInvalidPathException(): void
    {
        $root = $this->createTemporaryDirectory();
        $manifestFile = $this->writeAssetFile($root, 'manifest.json', '{invalid');

        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('manifest JSON could not be decoded');

        AssetManifest::fromFile($manifestFile);
    }

    public function testNonArrayManifestJsonThrowsInvalidPathException(): void
    {
        $root = $this->createTemporaryDirectory();
        $manifestFile = $this->writeAssetFile($root, 'manifest.json', 'true');

        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('manifest JSON must decode to an object');

        AssetManifest::fromFile($manifestFile);
    }

    public function testInvalidManifestSourcePathIsRejected(): void
    {
        $this->expectException(InvalidAssetPathException::class);

        new AssetManifest(['../app.css' => 'app.css']);
    }

    public function testInvalidManifestTargetIsRejected(): void
    {
        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('manifest entries must be strings or arrays with a string "file" value');

        new AssetManifest(['app.css' => ['file' => 123]]);
    }
}
