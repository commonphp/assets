# Testing And QA

CommonPHP Assets includes a focused PHPUnit suite for unit-level behavior.

## Install Dependencies

```bash
composer install
```

The package bootstrap first looks for `package/assets/vendor/autoload.php` and then falls back to the workspace root `vendor/autoload.php`. This supports both standalone package development and monorepo development.

## Run PHPUnit

From the package directory:

```bash
vendor/bin/phpunit -c phpunit.xml.dist
```

From the workspace root on Windows:

```bash
vendor\bin\phpunit.bat -c package\assets\phpunit.xml.dist
```

## Current Test Coverage

The unit suite covers:

- asset value object metadata and body reads;
- cache-control header generation;
- ETag and Last-Modified freshness checks;
- manifest loading, URL generation, local path lookup, and invalid entries;
- path normalization and traversal rejection;
- multi-root and prefixed-root resolution;
- MIME type defaults, overrides, and fallbacks;
- asset URL generation;
- response headers, empty HEAD bodies, and response read failures;
- asset request extraction from HTTP requests;
- manager delegation and custom resolver behavior;
- HTTP surface support checks, method handling, and error mapping;
- asset type classification;
- Markdown conversion;
- package exception factories and public contracts.

## Manual Review Areas

Manual review should still cover deploy-specific web server behavior, CDN cache rules, and whether application routes register the asset surface before broader catch-all surfaces.
