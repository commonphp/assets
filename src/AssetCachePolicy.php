<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\HTTP\Request;
use InvalidArgumentException;

final readonly class AssetCachePolicy
{
    public function __construct(
        private bool $public = true,
        private ?int $maxAge = 3600,
        private bool $immutable = false,
        private bool $mustRevalidate = false,
        private bool $sendEtag = true,
        private bool $sendLastModified = true,
        private bool $noStore = false,
        private bool $noCache = false,
    ) {
        if ($this->maxAge !== null && $this->maxAge < 0) {
            throw new InvalidArgumentException('Asset cache max age cannot be negative.');
        }
    }

    public static function public(?int $maxAge = 3600, bool $immutable = false): self
    {
        return new self(true, $maxAge, $immutable);
    }

    public static function private(?int $maxAge = 0): self
    {
        return new self(false, $maxAge, false, true);
    }

    public static function immutable(int $maxAge = 31536000): self
    {
        return new self(true, $maxAge, true);
    }

    public static function noCache(bool $noStore = false): self
    {
        return new self(false, 0, false, true, true, true, $noStore, true);
    }

    /**
     * @return array<string, string>
     */
    public function headers(Asset $asset): array
    {
        $headers = [
            'Cache-Control' => $this->cacheControlHeader(),
        ];

        if ($this->sendEtag) {
            $headers['ETag'] = $asset->etag();
        }

        if ($this->sendLastModified) {
            $headers['Last-Modified'] = $asset->lastModifiedHeader();
        }

        if (!$this->noStore && $this->maxAge !== null) {
            $headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $this->maxAge) . ' GMT';
        }

        return $headers;
    }

    public function isFresh(Request $request, Asset $asset): bool
    {
        $ifNoneMatch = $request->firstHeader('If-None-Match');

        if ($this->sendEtag && $ifNoneMatch !== null) {
            return $this->matchesEtag($ifNoneMatch, $asset->etag());
        }

        $ifModifiedSince = $request->firstHeader('If-Modified-Since');

        if ($this->sendLastModified && $ifModifiedSince !== null) {
            $modifiedSince = strtotime($ifModifiedSince);

            return $modifiedSince !== false && $modifiedSince >= $asset->modifiedAt();
        }

        return false;
    }

    public function cacheControlHeader(): string
    {
        if ($this->noStore) {
            return 'no-store, no-cache, max-age=0, must-revalidate';
        }

        $parts = [$this->public ? 'public' : 'private'];

        if ($this->noCache) {
            $parts[] = 'no-cache';
        }

        if ($this->maxAge !== null) {
            $parts[] = 'max-age=' . $this->maxAge;
        }

        if ($this->immutable) {
            $parts[] = 'immutable';
        }

        if ($this->mustRevalidate) {
            $parts[] = 'must-revalidate';
        }

        return implode(', ', $parts);
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function maxAge(): ?int
    {
        return $this->maxAge;
    }

    public function isImmutable(): bool
    {
        return $this->immutable;
    }

    public function sendsEtag(): bool
    {
        return $this->sendEtag;
    }

    public function sendsLastModified(): bool
    {
        return $this->sendLastModified;
    }

    private function matchesEtag(string $header, string $etag): bool
    {
        foreach (explode(',', $header) as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '*') {
                return true;
            }

            if (str_starts_with($candidate, 'W/')) {
                $candidate = substr($candidate, 2);
            }

            if ($candidate === $etag) {
                return true;
            }
        }

        return false;
    }

}
