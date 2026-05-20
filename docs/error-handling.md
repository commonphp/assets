# Error Handling

Direct resolver and manager calls throw package exceptions. The HTTP surface catches those exceptions and converts them to HTTP responses.

## Exception Types

- `AssetException` is the base package exception.
- `AssetNotFoundException` means no registered root contained the requested file.
- `InvalidAssetPathException` means the virtual path, root, manifest entry, or URL shape is invalid.
- `UnreadableAssetException` means a resolved asset cannot be read.
- `AssetResponseException` means the asset was resolved but the response body could not be created.

## Direct Usage

```php
<?php

use CommonPHP\Assets\Exceptions\AssetNotFoundException;

try {
    $asset = $assets->resolve('missing.css');
} catch (AssetNotFoundException) {
    // Application-specific fallback.
}
```

## Surface Usage

```php
<?php

$response = $assetSurface->handle($request);
```

The surface returns simple text responses for failures. Applications that need custom error pages can wrap the surface or call the manager directly from their own controller.

## Security-Relevant Failures

Invalid paths are treated as client errors. The package avoids echoing the rejected path into HTTP responses so traversal attempts and malformed input are not reflected back to users.
