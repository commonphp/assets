<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\AssetSurface;
use CommonPHP\Assets\Contracts\AssetResolverInterface;
use CommonPHP\Assets\Exceptions\AssetException;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use CommonPHP\Assets\Tests\Fixtures\TemporaryAssetsTrait;
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use PHPUnit\Framework\TestCase;

final class AssetSurfaceTest extends TestCase
{
    use TemporaryAssetsTrait;

    public function testItImplementsHttpSurfaceAndChecksSupportedPaths(): void
    {
        $surface = new AssetSurface(pathPrefix: '/static');

        self::assertInstanceOf(HttpSurfaceInterface::class, $surface);
        self::assertTrue($surface->supports(new Request('GET', '/static')));
        self::assertTrue($surface->supports(new Request('GET', '/static/css/app.css')));
        self::assertFalse($surface->supports(new Request('GET', '/assets/css/app.css')));
        self::assertSame('/static', $surface->pathPrefix());
        self::assertInstanceOf(AssetManager::class, $surface->manager());
    }

    public function testRootMountedSurfaceSupportsEveryPath(): void
    {
        $surface = new AssetSurface(pathPrefix: '/');

        self::assertTrue($surface->supports(new Request('GET', '/anything.css')));
    }

    public function testItServesGetAndHeadRequests(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $surface = new AssetSurface(AssetManager::fromRoot($root));

        $get = $surface->handle(new Request('GET', '/assets/css/app.css'));
        $head = $surface->handle(new Request('HEAD', '/assets/css/app.css'));

        self::assertSame(ResponseStatus::OK->value, $get->statusCode());
        self::assertSame('body {}', $get->body());
        self::assertSame(ResponseStatus::OK->value, $head->statusCode());
        self::assertSame('', $head->body());
        self::assertSame('7', $head->header('Content-Length'));
    }

    public function testUnsupportedMethodsReturnMethodNotAllowed(): void
    {
        $surface = new AssetSurface();
        $response = $surface->handle(new Request('POST', '/assets/css/app.css'));

        self::assertSame(ResponseStatus::METHOD_NOT_ALLOWED->value, $response->statusCode());
        self::assertSame('GET, HEAD', $response->header('Allow'));
    }

    public function testMissingAndMountRootRequestsReturnNotFound(): void
    {
        $surface = new AssetSurface(AssetManager::fromRoot($this->createTemporaryDirectory()));

        self::assertSame(ResponseStatus::NOT_FOUND->value, $surface->handle(new Request('GET', '/assets'))->statusCode());
        self::assertSame(ResponseStatus::NOT_FOUND->value, $surface->handle(new Request('GET', '/assets/missing.css'))->statusCode());
    }

    public function testInvalidPathsReturnBadRequest(): void
    {
        $surface = new AssetSurface(AssetManager::fromRoot($this->createTemporaryDirectory()));

        $response = $surface->handle(new Request('GET', '/assets/%2e%2e/secret.txt'));

        self::assertSame(ResponseStatus::BAD_REQUEST->value, $response->statusCode());
        self::assertSame('Invalid asset path.', $response->body());
    }

    public function testUnreadableAssetsReturnForbidden(): void
    {
        $surface = new AssetSurface(new AssetManager(new class implements AssetResolverInterface {
            public function resolve(string $path): Asset
            {
                throw UnreadableAssetException::forPath($path);
            }

            public function exists(string $path): bool
            {
                return false;
            }

            public function url(string $path, string $basePath = '/assets'): string
            {
                return $basePath . '/' . $path;
            }
        }));

        $response = $surface->handle(new Request('GET', '/assets/private.css'));

        self::assertSame(ResponseStatus::FORBIDDEN->value, $response->statusCode());
        self::assertSame('Asset is not readable.', $response->body());
    }

    public function testAssetExceptionsReturnInternalServerError(): void
    {
        $surface = new AssetSurface(new AssetManager(new class implements AssetResolverInterface {
            public function resolve(string $path): Asset
            {
                throw new AssetException('Failure.');
            }

            public function exists(string $path): bool
            {
                return false;
            }

            public function url(string $path, string $basePath = '/assets'): string
            {
                return $basePath . '/' . $path;
            }
        }));

        $response = $surface->handle(new Request('GET', '/assets/app.css'));

        self::assertSame(ResponseStatus::INTERNAL_SERVER_ERROR->value, $response->statusCode());
        self::assertSame('Unable to serve asset.', $response->body());
    }
}
