# Resolving Assets

`AssetResolver` maps virtual asset paths to files inside registered roots.

## Roots

```php
<?php

use CommonPHP\Assets\AssetResolver;

$resolver = new AssetResolver();
$resolver->addRoot(__DIR__ . '/public/assets');
$resolver->addRoot(__DIR__ . '/packages/blog/assets', 'blog');
```

Roots must be existing directories. The resolver stores real paths and verifies that resolved candidates stay inside their roots.

## Path Rules

Asset paths are slash-separated virtual paths:

```php
$asset = $resolver->resolve('css/app.css');
```

The resolver rejects:

- empty paths when a concrete asset is required;
- null bytes;
- `..` traversal segments;
- encoded traversal segments such as `%2e%2e`;
- URLs;
- stream or filesystem schemes;
- paths that resolve outside an asset root.

Leading slashes and duplicate separators are normalized away.

## Prefixed Roots

```php
<?php

$resolver->addRoot(__DIR__ . '/vendor/acme/assets', 'vendor/acme');

$asset = $resolver->resolve('vendor/acme/panel.css');
```

The virtual prefix is removed before the resolver checks the prefixed root.

## Asset Metadata

Resolving returns an `Asset` value object with:

- public path;
- real filesystem path;
- filename;
- extension;
- MIME type;
- size;
- modified timestamp;
- asset type;
- ETag;
- Last-Modified header value.

Use `contents()` only when you are ready to send or inspect the body.
