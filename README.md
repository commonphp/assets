# CommonPHP Assets

CommonPHP Assets provides asset resolution and serving support for CommonPHP applications. It helps locate, identify, and return static application or package assets through the HTTP layer.

The package is intended to make assets available without requiring applications to expose their full source or package structure directly through the public web root.

## Requirements

- PHP `^8.5`
- `comphp/runtime:^0.3`
- `comphp/http:^0.3`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/assets
```

## Usage

```php
<?php

// TODO: Write usage
```

## Package Notes

This package should handle asset paths, MIME types, cache headers, and asset responses. It should not become a full filesystem abstraction; virtual mounts and file operations belong in `comphp/filesystem`.

## Error Handling

Missing assets, unreadable files, invalid paths, and response failures should throw package-specific exceptions or return appropriate HTTP responses through the asset surface.

## Documentation

- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
