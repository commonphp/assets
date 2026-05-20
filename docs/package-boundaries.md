# Package Boundaries

CommonPHP Assets owns static asset resolution, asset URLs, cache headers, asset responses, and the HTTP surface used to serve assets.

## This Package Provides

- safe virtual path normalization;
- root-based asset lookup;
- manifest lookup for built assets;
- MIME type detection;
- asset metadata;
- ETag and Last-Modified handling;
- `GET` and `HEAD` asset responses;
- an HTTP surface for `/assets`-style mounts.

## This Package Does Not Provide

- general-purpose filesystem operations;
- file uploads;
- image transformation;
- JavaScript or CSS bundling;
- CDN synchronization;
- authentication or authorization;
- template rendering;
- route matching beyond its HTTP surface prefix.

## Related Packages

- `comphp/http` owns request and response primitives, middleware, surface registration, and response emission.
- `comphp/filesystem` owns virtual mounts and file operations.
- `comphp/web` may compose assets with routing, UI, and page rendering.
- `comphp/docs` may use asset-style helpers, but documentation parsing and navigation belong there.
