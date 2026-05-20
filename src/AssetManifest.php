<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Contracts\AssetManifestInterface;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use JsonException;

class AssetManifest implements AssetManifestInterface
{
    /**
     * @var array<string, string>
     */
    private array $entries = [];

    /**
     * @param array<string, mixed> $entries
     */
    public function __construct(array $entries = [])
    {
        foreach ($entries as $source => $target) {
            $this->entries[AssetResolver::normalizePath((string) $source)] = $this->normalizeTarget($source, $target);
        }
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function fromFile(string $path): self
    {
        $contents = @file_get_contents($path);

        if (!is_string($contents)) {
            throw UnreadableAssetException::forPath($path);
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidAssetPathException::forPath($path, 'manifest JSON could not be decoded: ' . $exception->getMessage());
        }

        if (!is_array($decoded)) {
            throw InvalidAssetPathException::forPath($path, 'manifest JSON must decode to an object');
        }

        return new self($decoded);
    }

    public function has(string $path): bool
    {
        return isset($this->entries[AssetResolver::normalizePath($path)]);
    }

    public function get(string $path): ?string
    {
        return $this->entries[AssetResolver::normalizePath($path)] ?? null;
    }

    public function path(string $path): string
    {
        $target = $this->get($path);

        if ($target === null) {
            return AssetResolver::normalizePath($path);
        }

        if (AssetResolver::isExternalUrl($target)) {
            throw InvalidAssetPathException::forPath($path, 'manifest target is an external URL');
        }

        return AssetResolver::normalizePath($target);
    }

    public function url(string $path, string $basePath = '/assets'): string
    {
        $target = $this->get($path) ?? AssetResolver::normalizePath($path);

        if (AssetResolver::isExternalUrl($target)) {
            return $target;
        }

        return AssetResolver::joinUrl($basePath, $target);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @param array-key $source
     */
    private function normalizeTarget(int|string $source, mixed $target): string
    {
        if (is_array($target) && isset($target['file']) && is_string($target['file'])) {
            $target = $target['file'];
        }

        if (!is_string($target)) {
            throw InvalidAssetPathException::forPath((string) $source, 'manifest entries must be strings or arrays with a string "file" value');
        }

        $target = trim($target);

        if (AssetResolver::isExternalUrl($target)) {
            return $target;
        }

        return AssetResolver::normalizePath($target);
    }

}
