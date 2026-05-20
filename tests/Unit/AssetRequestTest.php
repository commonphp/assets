<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\AssetRequest;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;
use PHPUnit\Framework\TestCase;

final class AssetRequestTest extends TestCase
{
    public function testItBuildsARequestForAnAssetPath(): void
    {
        $request = new AssetRequest('css/app.css', '/static', RequestMethod::HEAD, headers: ['Accept' => 'text/css']);

        self::assertSame('css/app.css', $request->assetPath());
        self::assertTrue($request->hasAssetPath());
        self::assertSame('/static', $request->mountPath());
        self::assertSame('/static/css/app.css', $request->path());
        self::assertSame(RequestMethod::HEAD, $request->method());
        self::assertSame('text/css', $request->header('Accept'));
    }

    public function testItAllowsEmptyAssetPathForMountRequests(): void
    {
        $request = new AssetRequest('', '/assets');

        self::assertSame('', $request->assetPath());
        self::assertFalse($request->hasAssetPath());
        self::assertSame('/assets', $request->path());
    }

    public function testItExtractsAssetPathFromHttpRequests(): void
    {
        $source = new Request(
            'GET',
            '/assets/css/app.css?version=1',
            ['Accept' => 'text/css'],
            'body',
            ['version' => '1'],
            ['parsed' => true],
            ['session' => 'abc'],
            ['file' => 'upload'],
            ['REMOTE_ADDR' => '127.0.0.1'],
            attributes: ['route' => 'assets'],
        );

        $request = AssetRequest::fromRequest($source, '/assets');

        self::assertSame('css/app.css', $request->assetPath());
        self::assertSame('/assets/css/app.css', $request->path());
        self::assertSame('version=1', $request->queryString());
        self::assertSame(['version' => '1'], $request->queryParams());
        self::assertSame(['parsed' => true], $request->parsedBody());
        self::assertSame(['session' => 'abc'], $request->cookies());
        self::assertSame(['file' => 'upload'], $request->files());
        self::assertSame('127.0.0.1', $request->server('REMOTE_ADDR'));
        self::assertSame('assets', $request->attribute('route'));
    }

    public function testPathFromRequestHandlesMountRootAndRootMount(): void
    {
        self::assertSame('', AssetRequest::pathFromRequest(new Request('GET', '/assets/'), '/assets'));
        self::assertSame('css/app.css', AssetRequest::pathFromRequest(new Request('GET', '/css/app.css'), '/'));
    }

    public function testPathFromRequestRejectsRequestsOutsideMount(): void
    {
        $this->expectException(InvalidAssetPathException::class);
        $this->expectExceptionMessage('request path is outside mount "/assets"');

        AssetRequest::pathFromRequest(new Request('GET', '/api/css/app.css'), '/assets');
    }

    public function testMountPathsAreNormalized(): void
    {
        self::assertSame('/assets', AssetRequest::normalizeMountPath('assets/'));
        self::assertSame('/', AssetRequest::normalizeMountPath('/'));
    }

    public function testInvalidAssetPathsAreRejectedByConstructor(): void
    {
        $this->expectException(InvalidAssetPathException::class);

        new AssetRequest('../secret.txt');
    }
}
