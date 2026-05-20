# Manifest Assets

```php
<?php

declare(strict_types=1);

use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\AssetManifest;
use CommonPHP\Assets\AssetResolver;

$manifest = AssetManifest::fromFile(__DIR__ . '/public/assets/manifest.json');
$resolver = new AssetResolver(__DIR__ . '/public/assets', manifest: $manifest);
$assets = new AssetManager($resolver);

echo $assets->url('css/app.css');
```

If the manifest maps `css/app.css` to `css/app.abc123.css`, the URL helper returns `/assets/css/app.abc123.css` and the resolver serves the built file.
