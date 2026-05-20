<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Contracts\AssetResolverInterface;
use CommonPHP\Assets\Exceptions\AssetException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;

class AssetManager implements AssetResolverInterface
{
    public function __construct(
        private AssetResolverInterface $resolver = new AssetResolver(),
        private AssetCachePolicy $cachePolicy = new AssetCachePolicy(),
        private string $basePath = '/assets',
    ) {
    }

    public static function fromRoot(string $root, string $basePath = '/assets', string $prefix = ''): self
    {
        return (new self(new AssetResolver(), new AssetCachePolicy(), $basePath))->addRoot($root, $prefix);
    }

    public function addRoot(string $root, string $prefix = ''): static
    {
        if (!$this->resolver instanceof AssetResolver) {
            throw new AssetException('Asset roots can only be added to the native AssetResolver.');
        }

        $this->resolver->addRoot($root, $prefix);

        return $this;
    }

    public function useResolver(AssetResolverInterface $resolver): static
    {
        $this->resolver = $resolver;

        return $this;
    }

    public function resolver(): AssetResolverInterface
    {
        return $this->resolver;
    }

    public function useCachePolicy(AssetCachePolicy $cachePolicy): static
    {
        $this->cachePolicy = $cachePolicy;

        return $this;
    }

    public function cachePolicy(): AssetCachePolicy
    {
        return $this->cachePolicy;
    }

    public function useBasePath(string $basePath): static
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function resolve(string $path): Asset
    {
        return $this->resolver->resolve($path);
    }

    public function exists(string $path): bool
    {
        return $this->resolver->exists($path);
    }

    public function url(string $path, string $basePath = '/assets'): string
    {
        return $this->resolver->url($path, $basePath === '/assets' ? $this->basePath : $basePath);
    }

    public function response(Asset|AssetRequest|string $asset, ?Request $request = null): AssetResponse
    {
        if ($asset instanceof AssetRequest) {
            $request = $request ?? $asset;
            $asset = $asset->assetPath();
        }

        if (is_string($asset)) {
            $asset = $this->resolve($asset);
        }

        if ($request !== null && $this->cachePolicy->isFresh($request, $asset)) {
            return AssetResponse::notModified($asset, $this->cachePolicy);
        }

        $includeBody = $request === null || $request->method() !== RequestMethod::HEAD;

        return AssetResponse::fromAsset($asset, $this->cachePolicy, $includeBody);
    }

    public function handle(Request $request, string $mountPath = '/assets'): AssetResponse
    {
        return $this->response(AssetRequest::fromRequest($request, $mountPath), $request);
    }

}
