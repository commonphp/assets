# MIME Types

`MimeTypeResolver` maps file extensions to content types and can fall back to PHP's file information support.

## Defaults

```php
<?php

use CommonPHP\Assets\MimeTypeResolver;

$mimeTypes = new MimeTypeResolver();

echo $mimeTypes->resolve('css/app.css');
```

Common browser asset types are included: CSS, JavaScript, JSON, images, fonts, audio, video, WebAssembly, SVG, Markdown, YAML, XML, and plain text.

## Custom Types

```php
<?php

$mimeTypes = new MimeTypeResolver([
    'webmanifest' => 'application/manifest+json; charset=utf-8',
]);
```

You can also clone with one additional type:

```php
<?php

$mimeTypes = $mimeTypes->withType('avifs', 'image/avif-sequence');
```

## Fallbacks

Unknown extensions return `application/octet-stream` by default. Pass a custom fallback when your application prefers a different default:

```php
<?php

$mimeTypes = new MimeTypeResolver(fallback: 'text/plain; charset=utf-8', useFinfo: false);
```

Disable `useFinfo` when you need deterministic extension-only behavior in tests or constrained environments.
