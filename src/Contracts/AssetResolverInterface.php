<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Contracts;

use CommonPHP\Assets\Asset;

interface AssetResolverInterface
{
    public function resolve(string $path): Asset;

    public function exists(string $path): bool;

    public function url(string $path, string $basePath = '/assets'): string;
}
