# Usage

The package has three common layers:

- `AssetResolver` finds and describes assets.
- `AssetManager` creates responses and URLs.
- `AssetSurface` plugs the manager into the HTTP package.

## Create A Manager

```php
<?php

use CommonPHP\Assets\AssetManager;

$assets = AssetManager::fromRoot(__DIR__ . '/public/assets');
```

`fromRoot()` is the smallest setup for a single public asset directory.

## Add Multiple Roots

```php
<?php

use CommonPHP\Assets\AssetManager;

$assets = (new AssetManager())
    ->addRoot(__DIR__ . '/public/assets')
    ->addRoot(__DIR__ . '/vendor/acme/package/assets', 'vendor/acme');
```

The optional prefix maps a root under a virtual path. In this example, `vendor/acme/logo.svg` resolves inside the vendor asset directory.

## Resolve An Asset

```php
<?php

$asset = $assets->resolve('images/logo.svg');

echo $asset->mimeType();
echo $asset->size();
echo $asset->etag();
```

`Asset` is a read-only value object. It contains the public asset path, real file path, MIME type, size, modified time, type, ETag, and body reader.

## Create A Response

```php
<?php

$response = $assets->response('images/logo.svg');
```

The response includes content type, content length, `nosniff`, ETag, Last-Modified, Expires, and Cache-Control headers.

## Handle Conditional Requests

```php
<?php

use CommonPHP\Assets\AssetRequest;

$assetRequest = AssetRequest::fromRequest($request, '/assets');
$response = $assets->response($assetRequest, $request);
```

When `If-None-Match` or `If-Modified-Since` matches the resolved asset, the manager returns a `304 Not Modified` response.
