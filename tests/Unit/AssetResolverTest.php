<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\AssetManifest;
use CommonPHP\Assets\AssetResolver;
use CommonPHP\Assets\Contracts\AssetResolverInterface;
use CommonPHP\Assets\Exceptions\AssetNotFoundException;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\Assets\MimeTypeResolver;
use CommonPHP\Assets\Tests\Fixtures\TemporaryAssetsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetResolverTest extends TestCase
{
    use TemporaryAssetsTrait;

    public function testItImplementsTheContractAndResolvesFilesFromRoots(): void
    {
        $root = $this->createTemporaryDirectory();
        $file = $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $resolver = new AssetResolver($root, new MimeTypeResolver(useFinfo: false));

        $asset = $resolver->resolve('/css/app.css');

        self::assertInstanceOf(AssetResolverInterface::class, $resolver);
        self::assertSame('css/app.css', $asset->path());
        self::assertSame(realpath($file), $asset->realPath());
        self::assertSame('text/css; charset=utf-8', $asset->mimeType());
        self::assertTrue($resolver->exists('css/app.css'));
        self::assertFalse($resolver->exists('missing.css'));
    }

    public function testItResolvesMultipleAndPrefixedRoots(): void
    {
        $appRoot = $this->createTemporaryDirectory();
        $vendorRoot = $this->createTemporaryDirectory();
        $this->writeAssetFile($appRoot, 'css/app.css', 'body {}');
        $vendorFile = $this->writeAssetFile($vendorRoot, 'panel.css', '.panel {}');
        $resolver = new AssetResolver([
            $appRoot,
            'vendor/acme' => $vendorRoot,
        ], new MimeTypeResolver(useFinfo: false));

        self::assertSame(realpath($vendorFile), $resolver->resolve('vendor/acme/panel.css')->realPath());
        self::assertSame('vendor/acme/panel.css', $resolver->resolve('vendor/acme/panel.css')->path());
    }

    public function testItUsesManifestTargetsForResolutionAndUrls(): void
    {
        $root = $this->createTemporaryDirectory();
        $builtFile = $this->writeAssetFile($root, 'css/app.123.css', 'body {}');
        $resolver = new AssetResolver(
            $root,
            new MimeTypeResolver(useFinfo: false),
            new AssetManifest(['css/app.css' => 'css/app.123.css']),
        );

        $asset = $resolver->resolve('css/app.css');

        self::assertSame('css/app.css', $asset->path());
        self::assertSame(realpath($builtFile), $asset->realPath());
        self::assertSame('/static/css/app.123.css', $resolver->url('css/app.css', '/static'));
    }

    public function testExternalManifestTargetsCannotBeResolvedLocally(): void
    {
        $root = $this->createTemporaryDirectory();
        $resolver = new AssetResolver(
            $root,
            new MimeTypeResolver(useFinfo: false),
            new AssetManifest(['remote.js' => 'https://cdn.example.test/remote.js']),
        );

        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('manifest target is an external URL and cannot be served');

        $resolver->resolve('remote.js');
    }

    public function testMissingAssetsThrowAssetNotFoundException(): void
    {
        $resolver = new AssetResolver($this->createTemporaryDirectory());

        $this->expectException(AssetNotFoundException::class);
        $this->expectExceptionMessage('Asset not found: "missing.css".');

        $resolver->resolve('missing.css');
    }

    public function testDirectoriesAreNotResolvedAsAssets(): void
    {
        $root = $this->createTemporaryDirectory();
        mkdir($root . DIRECTORY_SEPARATOR . 'css');
        $resolver = new AssetResolver($root);

        $this->expectException(AssetNotFoundException::class);

        $resolver->resolve('css');
    }

    #[DataProvider('normalizePathProvider')]
    public function testItNormalizesAssetPaths(string $path, string $expected): void
    {
        self::assertSame($expected, AssetResolver::normalizePath($path));
    }

    #[DataProvider('invalidPathProvider')]
    public function testItRejectsInvalidAssetPaths(string $path): void
    {
        $this->expectException(InvalidAssetPathException::class);

        AssetResolver::normalizePath($path);
    }

    public function testItAllowsEmptyPathOnlyWhenRequested(): void
    {
        self::assertSame('', AssetResolver::normalizePath('', true));

        $this->expectException(InvalidAssetPathException::class);

        AssetResolver::normalizePath('');
    }

    public function testItRejectsInvalidRoots(): void
    {
        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('asset root must be an existing directory');

        new AssetResolver($this->createTemporaryDirectory() . '/missing');
    }

    public function testItCanSwapManifestAndMimeTypeResolver(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'style.custom', 'body {}');
        $resolver = new AssetResolver($root);

        $resolver
            ->useManifest(AssetManifest::empty())
            ->useMimeTypes(new MimeTypeResolver(['custom' => 'text/custom'], useFinfo: false));

        self::assertInstanceOf(AssetManifest::class, $resolver->manifest());
        self::assertSame('text/custom', $resolver->mimeTypes()->resolve('style.custom'));
        self::assertSame('text/custom', $resolver->resolve('style.custom')->mimeType());
    }

    public function testItExposesRegisteredRoots(): void
    {
        $root = $this->createTemporaryDirectory();
        $resolver = new AssetResolver($root);

        self::assertSame(realpath($root), $resolver->roots()[0]['root']);
        self::assertSame('', $resolver->roots()[0]['prefix']);
    }

    public function testUrlGenerationEncodesSegmentsAndSupportsBasePaths(): void
    {
        self::assertSame('/assets/icons/app%20logo.svg', AssetResolver::joinUrl('/assets', 'icons/app logo.svg'));
        self::assertSame('/icons/app.svg', AssetResolver::joinUrl('/', 'icons/app.svg'));
        self::assertSame('https://cdn.example.test/assets/icons/app.svg', AssetResolver::joinUrl('https://cdn.example.test/assets', 'icons/app.svg'));
    }

    public function testItDetectsExternalUrls(): void
    {
        self::assertTrue(AssetResolver::isExternalUrl('https://example.test/app.css'));
        self::assertTrue(AssetResolver::isExternalUrl('//cdn.example.test/app.css'));
        self::assertFalse(AssetResolver::isExternalUrl('/assets/app.css'));
    }

    public static function normalizePathProvider(): iterable
    {
        yield 'leading slash' => ['/css/app.css', 'css/app.css'];
        yield 'duplicate slashes' => ['css//app.css', 'css/app.css'];
        yield 'current directory' => ['./css/./app.css', 'css/app.css'];
        yield 'backslashes' => ['css\\app.css', 'css/app.css'];
        yield 'query stripped' => ['css/app.css?version=1', 'css/app.css'];
    }

    public static function invalidPathProvider(): iterable
    {
        yield 'traversal' => ['../app.css'];
        yield 'nested traversal' => ['css/../app.css'];
        yield 'encoded traversal' => ['css/%2e%2e/app.css'];
        yield 'null byte' => ["css/app.css\0"];
        yield 'http url' => ['https://example.test/app.css'];
        yield 'scheme' => ['php://filter/resource=app.css'];
        yield 'windows drive' => ['C:\\assets\\app.css'];
    }
}
