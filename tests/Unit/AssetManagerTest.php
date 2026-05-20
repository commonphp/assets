<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\AssetCachePolicy;
use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\AssetRequest;
use CommonPHP\Assets\Contracts\AssetResolverInterface;
use CommonPHP\Assets\Exceptions\AssetException;
use CommonPHP\Assets\MimeTypeResolver;
use CommonPHP\Assets\Tests\Fixtures\TemporaryAssetsTrait;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use PHPUnit\Framework\TestCase;

final class AssetManagerTest extends TestCase
{
    use TemporaryAssetsTrait;

    public function testItResolvesChecksExistenceAndGeneratesUrls(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $manager = AssetManager::fromRoot($root, '/static');

        self::assertSame('css/app.css', $manager->resolve('css/app.css')->path());
        self::assertTrue($manager->exists('css/app.css'));
        self::assertFalse($manager->exists('missing.css'));
        self::assertSame('/static/css/app.css', $manager->url('css/app.css'));
        self::assertSame('/cdn/css/app.css', $manager->url('css/app.css', '/cdn'));
        self::assertSame('/static', $manager->basePath());
    }

    public function testItCanSwapResolverCachePolicyAndBasePath(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $resolver = new \CommonPHP\Assets\AssetResolver($root, new MimeTypeResolver(useFinfo: false));
        $policy = AssetCachePolicy::immutable(100);
        $manager = new AssetManager();

        $manager
            ->useResolver($resolver)
            ->useCachePolicy($policy)
            ->useBasePath('/assets-v2');

        self::assertSame($resolver, $manager->resolver());
        self::assertSame($policy, $manager->cachePolicy());
        self::assertSame('/assets-v2/css/app.css', $manager->url('css/app.css'));
    }

    public function testAddRootRequiresNativeResolver(): void
    {
        $manager = new AssetManager(new class implements AssetResolverInterface {
            public function resolve(string $path): Asset
            {
                throw new AssetException('unused');
            }

            public function exists(string $path): bool
            {
                return false;
            }

            public function url(string $path, string $basePath = '/assets'): string
            {
                return $basePath . '/' . $path;
            }
        });

        $this->expectException(AssetException::class);
        $this->expectExceptionMessage('Asset roots can only be added to the native AssetResolver.');

        $manager->addRoot($this->createTemporaryDirectory());
    }

    public function testItCreatesResponsesAndHonorsConditionalRequests(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $manager = AssetManager::fromRoot($root);
        $response = $manager->response('css/app.css');
        $request = new Request('GET', '/assets/css/app.css', ['If-None-Match' => $response->header('ETag')]);

        self::assertSame(ResponseStatus::OK->value, $response->statusCode());
        self::assertSame('body {}', $response->body());
        self::assertSame(ResponseStatus::NOT_MODIFIED->value, $manager->response('css/app.css', $request)->statusCode());
    }

    public function testHeadRequestsReturnHeadersWithoutBody(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $manager = AssetManager::fromRoot($root);
        $request = new Request(RequestMethod::HEAD, '/assets/css/app.css');

        $response = $manager->response(AssetRequest::fromRequest($request), $request);

        self::assertSame(ResponseStatus::OK->value, $response->statusCode());
        self::assertSame('', $response->body());
        self::assertSame('7', $response->header('Content-Length'));
    }

    public function testHandleCreatesAssetRequestFromHttpRequest(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $manager = AssetManager::fromRoot($root);

        $response = $manager->handle(new Request('GET', '/static/css/app.css'), '/static');

        self::assertSame(ResponseStatus::OK->value, $response->statusCode());
        self::assertSame('body {}', $response->body());
    }
}
