<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\RequestScheme;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Request;

class AssetRequest extends Request
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     * @param array<string, mixed> $queryParams
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $serverParams
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        private readonly string $assetPath,
        private readonly string $mountPath = '/assets',
        RequestMethod|string $method = RequestMethod::GET,
        ?string $uri = null,
        array|HeaderBag $headers = [],
        string $body = '',
        array $queryParams = [],
        mixed $parsedBody = null,
        array $cookies = [],
        array $files = [],
        array $serverParams = [],
        RequestScheme|string|null $scheme = null,
        array $attributes = [],
    ) {
        $assetPath = AssetResolver::normalizePath($assetPath, true);
        $mountPath = self::normalizeMountPath($mountPath);

        parent::__construct(
            $method,
            $uri ?? self::assetUri($assetPath, $mountPath),
            $headers,
            $body,
            $queryParams,
            $parsedBody,
            $cookies,
            $files,
            $serverParams,
            $scheme,
            $attributes,
        );
    }

    public static function fromRequest(Request $request, string $mountPath = '/assets'): self
    {
        return new self(
            self::pathFromRequest($request, $mountPath),
            $mountPath,
            $request->method(),
            $request->uri(),
            $request->headers(),
            $request->body(),
            $request->queryParams(),
            $request->parsedBody(),
            $request->cookies(),
            $request->files(),
            $request->serverParams(),
            $request->scheme(),
            $request->attributes(),
        );
    }

    public static function pathFromRequest(Request $request, string $mountPath = '/assets'): string
    {
        $mountPath = self::normalizeMountPath($mountPath);
        $path = rtrim($request->path(), '/');

        if ($mountPath === '/') {
            return AssetResolver::normalizePath($request->path(), true);
        }

        if ($path === $mountPath) {
            return '';
        }

        if (str_starts_with($request->path(), $mountPath . '/')) {
            return AssetResolver::normalizePath(substr($request->path(), strlen($mountPath) + 1), true);
        }

        throw InvalidAssetPathException::forPath($request->path(), 'request path is outside mount "' . $mountPath . '"');
    }

    public static function normalizeMountPath(string $mountPath): string
    {
        $mountPath = trim($mountPath);

        if ($mountPath === '' || $mountPath === '/') {
            return '/';
        }

        return '/' . AssetResolver::normalizePath($mountPath);
    }

    public function assetPath(): string
    {
        return AssetResolver::normalizePath($this->assetPath, true);
    }

    public function hasAssetPath(): bool
    {
        return $this->assetPath() !== '';
    }

    public function mountPath(): string
    {
        return self::normalizeMountPath($this->mountPath);
    }

    private static function assetUri(string $assetPath, string $mountPath): string
    {
        $mountPath = self::normalizeMountPath($mountPath);

        if ($assetPath === '') {
            return $mountPath;
        }

        return rtrim($mountPath, '/') . '/' . $assetPath;
    }

}
