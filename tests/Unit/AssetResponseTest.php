<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\AssetCachePolicy;
use CommonPHP\Assets\AssetResponse;
use CommonPHP\Assets\Exceptions\AssetResponseException;
use CommonPHP\Assets\Tests\Fixtures\TemporaryAssetsTrait;
use CommonPHP\HTTP\Enums\ResponseStatus;
use PHPUnit\Framework\TestCase;

final class AssetResponseTest extends TestCase
{
    use TemporaryAssetsTrait;

    public function testItCreatesResponsesFromAssets(): void
    {
        $asset = $this->asset();
        $response = AssetResponse::fromAsset($asset, AssetCachePolicy::public(60));

        self::assertSame($asset, $response->asset());
        self::assertSame(ResponseStatus::OK->value, $response->statusCode());
        self::assertSame('body {}', $response->body());
        self::assertSame('text/css; charset=utf-8', $response->header('Content-Type'));
        self::assertSame('7', $response->header('Content-Length'));
        self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
        self::assertSame('public, max-age=60', $response->header('Cache-Control'));
        self::assertSame($asset->etag(), $response->header('ETag'));
    }

    public function testItCanCreateHeaderOnlyResponses(): void
    {
        $asset = $this->asset();
        $response = AssetResponse::fromAsset($asset, includeBody: false);

        self::assertSame('', $response->body());
        self::assertSame('7', $response->header('Content-Length'));
    }

    public function testItCreatesNotModifiedResponses(): void
    {
        $asset = $this->asset();
        $response = AssetResponse::notModified($asset, AssetCachePolicy::private());

        self::assertSame(ResponseStatus::NOT_MODIFIED->value, $response->statusCode());
        self::assertSame('', $response->body());
        self::assertSame($asset, $response->asset());
        self::assertSame('private, max-age=0, must-revalidate', $response->header('Cache-Control'));
    }

    public function testReadFailuresAreWrappedAsResponseExceptions(): void
    {
        $root = $this->createTemporaryDirectory();
        $file = $this->writeAssetFile($root, 'css/app.css', 'body {}');
        $asset = Asset::fromFile('css/app.css', $file);
        unlink($file);

        $this->expectException(AssetResponseException::class);
        $this->expectExceptionMessage('Unable to create asset response for "css/app.css"');

        AssetResponse::fromAsset($asset);
    }

    private function asset(): Asset
    {
        $root = $this->createTemporaryDirectory();
        $file = $this->writeAssetFile($root, 'css/app.css', 'body {}');

        return new Asset('css/app.css', $file, 'text/css; charset=utf-8', 7, 1700000000);
    }
}
