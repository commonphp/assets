<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Exceptions;

class AssetNotFoundException extends AssetException
{
    public static function forPath(string $path): self
    {
        return new self('Asset not found: "' . $path . '".');
    }
}
