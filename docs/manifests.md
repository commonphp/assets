# Manifests

`AssetManifest` maps source asset names to built asset names or external URLs.

Build tools commonly produce hashed filenames:

```json
{
  "css/app.css": "css/app.3f8c1d.css",
  "js/app.js": {
    "file": "js/app.a912b4.js"
  }
}
```

## Load A Manifest

```php
<?php

use CommonPHP\Assets\AssetManifest;
use CommonPHP\Assets\AssetResolver;

$manifest = AssetManifest::fromFile(__DIR__ . '/public/assets/manifest.json');

$resolver = new AssetResolver(__DIR__ . '/public/assets', manifest: $manifest);
```

The resolver serves the manifest target while keeping the requested public path on the returned `Asset`.

## Generate URLs

```php
<?php

echo $manifest->url('css/app.css');
```

The manifest returns `/assets/css/app.3f8c1d.css` for local targets and returns external HTTP URLs unchanged.

## Resolve Local Paths

```php
<?php

echo $manifest->path('css/app.css');
```

`path()` returns the local manifest target. It throws when the target is an external URL because external URLs cannot be served by the local resolver.

## Accepted Entry Shapes

Manifest values can be:

- a string local path;
- a string HTTP URL;
- an array with a string `file` value.

Invalid JSON, non-array data, traversal paths, and non-string entries raise package exceptions.
