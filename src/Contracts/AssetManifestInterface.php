<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Contracts;

interface AssetManifestInterface
{
    public function has(string $path): bool;

    public function get(string $path): ?string;

    public function path(string $path): string;

    public function url(string $path, string $basePath = '/assets'): string;

    /**
     * @return array<string, string>
     */
    public function all(): array;
}
