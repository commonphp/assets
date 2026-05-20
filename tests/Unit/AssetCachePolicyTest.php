<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\AssetCachePolicy;
use CommonPHP\HTTP\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AssetCachePolicyTest extends TestCase
{
    public function testDefaultHeadersIncludeCacheAndValidators(): void
    {
        $asset = $this->asset();
        $headers = (new AssetCachePolicy())->headers($asset);

        self::assertSame('public, max-age=3600', $headers['Cache-Control']);
        self::assertSame($asset->etag(), $headers['ETag']);
        self::assertSame($asset->lastModifiedHeader(), $headers['Last-Modified']);
        self::assertArrayHasKey('Expires', $headers);
    }

    public function testPolicyFactoriesBuildExpectedCacheControlHeaders(): void
    {
        self::assertSame('public, max-age=120', AssetCachePolicy::public(120)->cacheControlHeader());
        self::assertSame('public, max-age=31536000, immutable', AssetCachePolicy::immutable()->cacheControlHeader());
        self::assertSame('private, max-age=0, must-revalidate', AssetCachePolicy::private()->cacheControlHeader());
        self::assertSame('private, no-cache, max-age=0, must-revalidate', AssetCachePolicy::noCache()->cacheControlHeader());
        self::assertSame('no-store, no-cache, max-age=0, must-revalidate', AssetCachePolicy::noCache(noStore: true)->cacheControlHeader());
    }

    public function testNoStoreDoesNotSendExpires(): void
    {
        $headers = AssetCachePolicy::noCache(noStore: true)->headers($this->asset());

        self::assertArrayNotHasKey('Expires', $headers);
    }

    public function testNegativeMaxAgeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Asset cache max age cannot be negative.');

        new AssetCachePolicy(maxAge: -1);
    }

    public function testItReportsPolicyProperties(): void
    {
        $policy = AssetCachePolicy::immutable(99);

        self::assertTrue($policy->isPublic());
        self::assertSame(99, $policy->maxAge());
        self::assertTrue($policy->isImmutable());
        self::assertTrue($policy->sendsEtag());
        self::assertTrue($policy->sendsLastModified());
    }

    public function testFreshnessUsesStrongWeakAndWildcardEtags(): void
    {
        $asset = $this->asset();
        $policy = new AssetCachePolicy();

        self::assertTrue($policy->isFresh(new Request('GET', '/', ['If-None-Match' => $asset->etag()]), $asset));
        self::assertTrue($policy->isFresh(new Request('GET', '/', ['If-None-Match' => 'W/' . $asset->etag()]), $asset));
        self::assertTrue($policy->isFresh(new Request('GET', '/', ['If-None-Match' => '"other", ' . $asset->etag()]), $asset));
        self::assertTrue($policy->isFresh(new Request('GET', '/', ['If-None-Match' => '*']), $asset));
        self::assertFalse($policy->isFresh(new Request('GET', '/', ['If-None-Match' => '"other"']), $asset));
    }

    public function testIfNoneMatchTakesPrecedenceOverLastModified(): void
    {
        $asset = $this->asset();
        $policy = new AssetCachePolicy();
        $request = new Request('GET', '/', [
            'If-None-Match' => '"different"',
            'If-Modified-Since' => gmdate('D, d M Y H:i:s', $asset->modifiedAt() + 60) . ' GMT',
        ]);

        self::assertFalse($policy->isFresh($request, $asset));
    }

    public function testFreshnessUsesLastModifiedWhenEtagIsAbsent(): void
    {
        $asset = $this->asset();
        $policy = new AssetCachePolicy();

        self::assertTrue($policy->isFresh(new Request('GET', '/', [
            'If-Modified-Since' => gmdate('D, d M Y H:i:s', $asset->modifiedAt()) . ' GMT',
        ]), $asset));
        self::assertFalse($policy->isFresh(new Request('GET', '/', [
            'If-Modified-Since' => gmdate('D, d M Y H:i:s', $asset->modifiedAt() - 60) . ' GMT',
        ]), $asset));
        self::assertFalse($policy->isFresh(new Request('GET', '/', [
            'If-Modified-Since' => 'not a date',
        ]), $asset));
    }

    private function asset(): Asset
    {
        return new Asset('css/app.css', __FILE__, 'text/css; charset=utf-8', 123, 1700000000);
    }
}
