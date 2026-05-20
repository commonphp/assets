<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Exceptions\AssetResponseException;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Response;
use Throwable;

class AssetResponse extends Response
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function __construct(
        private readonly ?Asset $asset = null,
        string $body = '',
        ResponseStatus|int $status = ResponseStatus::OK,
        array|HeaderBag $headers = [],
    ) {
        parent::__construct($body, $status, $headers);
    }

    public static function fromAsset(
        Asset $asset,
        ?AssetCachePolicy $cachePolicy = null,
        bool $includeBody = true,
    ): self {
        $cachePolicy ??= new AssetCachePolicy();
        $headers = array_replace([
            'Content-Type' => $asset->mimeType(),
            'Content-Length' => (string) $asset->size(),
            'X-Content-Type-Options' => 'nosniff',
        ], $cachePolicy->headers($asset));

        try {
            $body = $includeBody ? $asset->contents() : '';
        } catch (Throwable $exception) {
            throw AssetResponseException::forPath($asset->path(), $exception->getMessage(), $exception);
        }

        return new self($asset, $body, ResponseStatus::OK, $headers);
    }

    public static function notModified(Asset $asset, ?AssetCachePolicy $cachePolicy = null): self
    {
        $cachePolicy ??= new AssetCachePolicy();

        return new self($asset, '', ResponseStatus::NOT_MODIFIED, $cachePolicy->headers($asset));
    }

    public function asset(): ?Asset
    {
        return $this->asset;
    }

}
