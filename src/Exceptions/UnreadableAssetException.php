<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Exceptions;

use Throwable;

class UnreadableAssetException extends AssetException
{
    public static function forPath(string $path, ?Throwable $previous = null): self
    {
        return new self('Asset is not readable: "' . $path . '".', 0, $previous);
    }
}
