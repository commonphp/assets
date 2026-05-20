<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Contracts\AssetManifestInterface;
use CommonPHP\Assets\Contracts\AssetResolverInterface;
use CommonPHP\Assets\Contracts\MimeTypeResolverInterface;
use CommonPHP\Assets\Exceptions\AssetNotFoundException;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;

class AssetResolver implements AssetResolverInterface
{
    /**
     * @var list<array{root: string, prefix: string}>
     */
    private array $roots = [];

    /**
     * @param string|iterable<string|int, string>|null $roots
     */
    public function __construct(
        string|iterable|null $roots = null,
        private MimeTypeResolverInterface $mimeTypes = new MimeTypeResolver(),
        private ?AssetManifestInterface $manifest = null,
    ) {
        if ($roots === null) {
            return;
        }

        if (is_string($roots)) {
            $this->addRoot($roots);

            return;
        }

        foreach ($roots as $prefix => $root) {
            $this->addRoot($root, is_string($prefix) ? $prefix : '');
        }
    }

    public function addRoot(string $root, string $prefix = ''): static
    {
        $this->roots[] = [
            'root' => $this->normalizeRoot($root),
            'prefix' => self::normalizePath($prefix, true),
        ];

        return $this;
    }

    public function useManifest(?AssetManifestInterface $manifest): static
    {
        $this->manifest = $manifest;

        return $this;
    }

    public function useMimeTypes(MimeTypeResolverInterface $mimeTypes): static
    {
        $this->mimeTypes = $mimeTypes;

        return $this;
    }

    public function resolve(string $path): Asset
    {
        $requestedPath = self::normalizePath($path);
        $manifestPath = $this->manifest?->get($requestedPath) ?? $requestedPath;

        if (self::isExternalUrl($manifestPath)) {
            throw InvalidAssetPathException::forPath($requestedPath, 'manifest target is an external URL and cannot be served');
        }

        $manifestPath = self::normalizePath($manifestPath);

        foreach ($this->roots as $entry) {
            $relativePath = $this->relativePathForRoot($manifestPath, $entry['prefix']);

            if ($relativePath === null) {
                continue;
            }

            $candidate = $this->resolveCandidate($entry['root'], $relativePath);
            $realPath = realpath($candidate);

            if ($realPath === false || !is_file($realPath)) {
                continue;
            }

            $this->assertWithinRoot($realPath, $entry['root']);

            if (!is_readable($realPath)) {
                throw UnreadableAssetException::forPath($requestedPath);
            }

            return Asset::fromFile($requestedPath, $realPath, $this->mimeTypes);
        }

        throw AssetNotFoundException::forPath($requestedPath);
    }

    public function exists(string $path): bool
    {
        try {
            $this->resolve($path);

            return true;
        } catch (InvalidAssetPathException | AssetNotFoundException | UnreadableAssetException) {
            return false;
        }
    }

    public function url(string $path, string $basePath = '/assets'): string
    {
        if ($this->manifest !== null) {
            return $this->manifest->url($path, $basePath);
        }

        return self::joinUrl($basePath, self::normalizePath($path));
    }

    /**
     * @return list<array{root: string, prefix: string}>
     */
    public function roots(): array
    {
        return $this->roots;
    }

    public function manifest(): ?AssetManifestInterface
    {
        return $this->manifest;
    }

    public function mimeTypes(): MimeTypeResolverInterface
    {
        return $this->mimeTypes;
    }

    public static function normalizePath(string $path, bool $allowEmpty = false): string
    {
        $original = $path;

        if (str_contains($original, "\0")) {
            throw InvalidAssetPathException::forPath($original, 'null bytes are not allowed');
        }

        $path = trim($path);

        if (self::isExternalUrl($path) || preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $path) === 1) {
            throw InvalidAssetPathException::forPath($original, 'URLs and schemes are not valid asset paths');
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);

        if (is_string($parsedPath)) {
            $path = $parsedPath;
        }

        $path = rawurldecode(str_replace('\\', '/', $path));
        $segments = [];

        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw InvalidAssetPathException::forPath($original, 'path traversal is not allowed');
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        if ($normalized === '' && !$allowEmpty) {
            throw InvalidAssetPathException::forPath($original, 'path cannot be empty');
        }

        return $normalized;
    }

    public static function isExternalUrl(string $path): bool
    {
        return preg_match('#^(?:https?:)?//#i', trim($path)) === 1;
    }

    public static function joinUrl(string $basePath, string $path): string
    {
        $path = self::normalizePath($path);
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));
        $basePath = trim($basePath);

        if ($basePath === '') {
            return '/' . $encodedPath;
        }

        if (self::isExternalUrl($basePath)) {
            return rtrim($basePath, '/') . '/' . $encodedPath;
        }

        $basePath = trim($basePath, '/');

        if ($basePath === '') {
            return '/' . $encodedPath;
        }

        return '/' . $basePath . '/' . $encodedPath;
    }

    private function normalizeRoot(string $root): string
    {
        if (str_contains($root, "\0")) {
            throw InvalidAssetPathException::forPath($root, 'null bytes are not allowed');
        }

        $root = trim($root);

        if ($root === '') {
            throw InvalidAssetPathException::forPath($root, 'asset roots cannot be empty');
        }

        $realPath = realpath($root);

        if ($realPath === false || !is_dir($realPath)) {
            throw InvalidAssetPathException::forPath($root, 'asset root must be an existing directory');
        }

        return rtrim($realPath, DIRECTORY_SEPARATOR);
    }

    private function relativePathForRoot(string $path, string $prefix): ?string
    {
        if ($prefix === '') {
            return $path;
        }

        if ($path === $prefix) {
            return '';
        }

        if (!str_starts_with($path, $prefix . '/')) {
            return null;
        }

        return substr($path, strlen($prefix) + 1);
    }

    private function resolveCandidate(string $root, string $relativePath): string
    {
        if ($relativePath === '') {
            return $root;
        }

        $candidate = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->assertWithinRoot($candidate, $root);

        return $candidate;
    }

    private function assertWithinRoot(string $path, string $root): void
    {
        $path = $this->comparablePath($path);
        $root = $this->comparablePath($root);

        if ($path !== $root && !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            throw InvalidAssetPathException::forPath($path, 'resolved path escapes the asset root');
        }
    }

    private function comparablePath(string $path): string
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
        }

        return $path;
    }

}
