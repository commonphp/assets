# HTTP Surface

`AssetSurface` implements `CommonPHP\HTTP\Contracts\HttpSurfaceInterface`.

## Register The Surface

```php
<?php

use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\AssetSurface;
use CommonPHP\HTTP\HttpApplication;

$assets = AssetManager::fromRoot(__DIR__ . '/public/assets');

$app = (new HttpApplication())
    ->surface('assets', new AssetSurface($assets), '/assets', priority: 20);
```

The HTTP package still owns request creation, middleware, surface lookup, and response emission. The assets package only decides whether a request belongs to assets and how to build the asset response.

## Supported Methods

`GET` returns the asset body. `HEAD` returns the same headers with an empty body.

Other methods return `405 Method Not Allowed` with:

```http
Allow: GET, HEAD
```

## Response Mapping

The surface maps asset exceptions to HTTP responses:

- missing asset: `404`;
- invalid asset path: `400`;
- unreadable asset: `403`;
- other asset failure: `500`.

This keeps application-level HTTP handling simple while preserving package-specific exceptions for direct manager or resolver usage.
