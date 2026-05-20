<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Exceptions;

class InvalidAssetPathException extends AssetException
{
    public static function forPath(string $path, string $reason): self
    {
        return new self('Invalid asset path "' . $path . '": ' . $reason);
    }
}
