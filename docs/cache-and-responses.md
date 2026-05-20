# Cache Policies And Responses

`AssetCachePolicy` controls freshness headers and conditional request handling. `AssetResponse` converts assets into HTTP responses.

## Default Policy

```php
<?php

use CommonPHP\Assets\AssetCachePolicy;

$policy = new AssetCachePolicy();
```

The default policy is public, sends ETags and Last-Modified headers, and uses a one-hour max age.

## Long-Lived Assets

```php
<?php

$policy = AssetCachePolicy::immutable();
```

Immutable assets are useful for hashed build outputs because the filename changes when the content changes.

## Private Or No-Cache Assets

```php
<?php

$private = AssetCachePolicy::private();
$noCache = AssetCachePolicy::noCache();
$noStore = AssetCachePolicy::noCache(noStore: true);
```

Private and no-cache policies are useful when static-like assets still depend on an authenticated context or deploy-specific data.

## Conditional Requests

The manager checks `If-None-Match` before `If-Modified-Since`, matching normal HTTP cache semantics:

```php
<?php

$response = $assets->response($assetRequest, $request);
```

Fresh requests return `304 Not Modified`. `HEAD` requests return headers without a response body.

## Response Headers

Successful asset responses include:

- `Content-Type`;
- `Content-Length`;
- `X-Content-Type-Options: nosniff`;
- `Cache-Control`;
- `ETag`;
- `Last-Modified`;
- `Expires`, when the policy allows storage.
