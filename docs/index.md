# CommonPHP Assets Documentation

CommonPHP Assets is the static asset package for CommonPHP applications. It resolves safe virtual asset paths to real files, reads build manifests, creates asset responses, applies cache headers, and exposes an HTTP surface for serving assets through `comphp/http`.

Assets should stay small and predictable. The package intentionally avoids becoming a filesystem abstraction, bundler, CDN client, template layer, or authorization system.

## Start Here

- [Getting started](getting-started.md)
- [Usage](usage.md)
- [Package boundaries](package-boundaries.md)

## Asset Concepts

- [Resolving assets](resolving-assets.md)
- [Manifests](manifests.md)
- [Cache policies and responses](cache-and-responses.md)
- [HTTP surface](http-surface.md)
- [MIME types](mime-types.md)
- [Markdown converter](markdown-converter.md)
- [Error handling](error-handling.md)

## Examples

- [Examples index](examples/index.md)
- [Basic assets](examples/basic-assets.md)
- [Manifest assets](examples/manifest-assets.md)
- [HTTP surface](examples/http-surface.md)
- [Custom resolver](examples/custom-resolver.md)

## Development

- [Testing and QA](testing.md)

## Public API Map

Entry points:

- `CommonPHP\Assets\AssetManager`
- `CommonPHP\Assets\AssetSurface`
- `CommonPHP\Assets\AssetResolver`

Asset objects:

- `CommonPHP\Assets\Asset`
- `CommonPHP\Assets\AssetRequest`
- `CommonPHP\Assets\AssetResponse`
- `CommonPHP\Assets\AssetCachePolicy`
- `CommonPHP\Assets\AssetManifest`
- `CommonPHP\Assets\MimeTypeResolver`
- `CommonPHP\Assets\MarkdownConverter`

Contracts:

- `CommonPHP\Assets\Contracts\AssetResolverInterface`
- `CommonPHP\Assets\Contracts\AssetManifestInterface`
- `CommonPHP\Assets\Contracts\MimeTypeResolverInterface`

Enums:

- `CommonPHP\Assets\Enums\AssetType`

Exceptions:

- `CommonPHP\Assets\Exceptions\AssetException`
- `CommonPHP\Assets\Exceptions\AssetNotFoundException`
- `CommonPHP\Assets\Exceptions\AssetResponseException`
- `CommonPHP\Assets\Exceptions\InvalidAssetPathException`
- `CommonPHP\Assets\Exceptions\UnreadableAssetException`
