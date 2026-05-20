<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Exceptions;

use Throwable;

class AssetResponseException extends AssetException
{
    public static function forPath(string $path, string $reason, ?Throwable $previous = null): self
    {
        return new self('Unable to create asset response for "' . $path . '": ' . $reason, 0, $previous);
    }
}
