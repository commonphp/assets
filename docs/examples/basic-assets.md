# Basic Assets

```php
<?php

declare(strict_types=1);

use CommonPHP\Assets\AssetManager;

$assets = AssetManager::fromRoot(__DIR__ . '/public/assets');

$asset = $assets->resolve('css/app.css');
$response = $assets->response($asset);

echo $asset->mimeType();
echo $response->header('Cache-Control');
```

Use `url()` when rendering links:

```php
<link rel="stylesheet" href="<?= htmlspecialchars($assets->url('css/app.css'), ENT_QUOTES) ?>">
```
