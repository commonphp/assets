# HTTP Surface

```php
<?php

declare(strict_types=1);

use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\AssetSurface;
use CommonPHP\HTTP\HttpApplication;

$assets = AssetManager::fromRoot(__DIR__ . '/public/assets');

$app = (new HttpApplication())
    ->surface('assets', new AssetSurface($assets), '/assets', 20);
```

Register the asset surface ahead of broader web surfaces so `/assets/...` requests do not fall through to page rendering.
