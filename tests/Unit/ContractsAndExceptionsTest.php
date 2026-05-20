<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\AssetManifest;
use CommonPHP\Assets\AssetResolver;
use CommonPHP\Assets\Contracts\AssetManifestInterface;
use CommonPHP\Assets\Contracts\AssetResolverInterface;
use CommonPHP\Assets\Contracts\MimeTypeResolverInterface;
use CommonPHP\Assets\Exceptions\AssetException;
use CommonPHP\Assets\Exceptions\AssetNotFoundException;
use CommonPHP\Assets\Exceptions\AssetResponseException;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use CommonPHP\Assets\MimeTypeResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContractsAndExceptionsTest extends TestCase
{
    public function testPublicImplementationsSatisfyTheirContracts(): void
    {
        self::assertInstanceOf(AssetResolverInterface::class, new AssetResolver());
        self::assertInstanceOf(AssetManifestInterface::class, new AssetManifest());
        self::assertInstanceOf(MimeTypeResolverInterface::class, new MimeTypeResolver());
    }

    public function testAssetResolverContractCanBeImplementedByApplications(): void
    {
        $resolver = new class implements AssetResolverInterface {
            public function resolve(string $path): Asset
            {
                return new Asset($path, __FILE__, 'text/plain', 0, 1);
            }

            public function exists(string $path): bool
            {
                return $path === 'exists.txt';
            }

            public function url(string $path, string $basePath = '/assets'): string
            {
                return rtrim($basePath, '/') . '/' . $path;
            }
        };

        self::assertSame('file.txt', $resolver->resolve('file.txt')->path());
        self::assertTrue($resolver->exists('exists.txt'));
        self::assertSame('/static/file.txt', $resolver->url('file.txt', '/static'));
    }

    public function testExceptionFactoriesCreateHelpfulMessages(): void
    {
        self::assertInstanceOf(RuntimeException::class, new AssetException('Base.'));
        self::assertSame('Asset not found: "missing.css".', AssetNotFoundException::forPath('missing.css')->getMessage());
        self::assertSame('Invalid asset path "../secret": traversal', InvalidAssetPathException::forPath('../secret', 'traversal')->getMessage());
        self::assertSame('Asset is not readable: "private.css".', UnreadableAssetException::forPath('private.css')->getMessage());
        self::assertSame(
            'Unable to create asset response for "app.css": failed',
            AssetResponseException::forPath('app.css', 'failed')->getMessage(),
        );
    }
}
