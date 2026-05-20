# Custom Resolver

Custom resolvers can implement `AssetResolverInterface` when an application needs lookup rules beyond native roots.

```php
<?php

declare(strict_types=1);

use CommonPHP\Assets\Asset;
use CommonPHP\Assets\AssetManager;
use CommonPHP\Assets\Contracts\AssetResolverInterface;

final class TenantAssetResolver implements AssetResolverInterface
{
    public function resolve(string $path): Asset
    {
        // Resolve a tenant-specific file and return an Asset.
    }

    public function exists(string $path): bool
    {
        // Return whether the tenant-specific asset exists.
    }

    public function url(string $path, string $basePath = '/assets'): string
    {
        // Generate the public tenant asset URL.
    }
}

$assets = new AssetManager(new TenantAssetResolver());
```

Keep custom resolvers strict about path normalization and root containment. The native resolver is a useful reference for those checks.
