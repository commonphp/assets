# Getting Started

CommonPHP Assets serves files from explicit asset roots through safe virtual paths.

## Install

```bash
composer require comphp/assets
```

In this monorepo, the package is also available through the workspace path repository and the root Composer autoloader.

## Serve A Directory

```php
<?php

declare(strict_types=1);

use CommonPHP\Assets\AssetManager;

$assets = AssetManager::fromRoot(__DIR__ . '/public/assets');

$response = $assets->response('css/app.css');

echo $response->statusCode();
echo $response->header('Content-Type');
```

Only files inside registered roots can be served. Absolute URLs, stream schemes, null bytes, and path traversal are rejected before filesystem access.

## Generate URLs

```php
<?php

$assets = AssetManager::fromRoot(__DIR__ . '/public/assets', '/assets');

echo $assets->url('css/app.css');
```

The URL helper encodes path segments and keeps applications from hand-building public asset paths in templates.

## Use The HTTP Surface

```php
<?php

use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\AssetSurface;
use CommonPHP\HTTP\HttpApplication;

$assetManager = AssetManager::fromRoot(__DIR__ . '/public/assets');

$app = (new HttpApplication())
    ->surface('assets', new AssetSurface($assetManager), '/assets', priority: 20);
```

`AssetSurface` supports `GET` and `HEAD`, returns `404` for missing files, and maps package exceptions to suitable HTTP responses.
