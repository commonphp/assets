<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\ResponseFactory;

class AssetSurface implements HttpSurfaceInterface
{
    public function supports(Request $request): bool
    {
        return $request->path() === '/assets' || str_starts_with($request->path(), '/assets/');
    }

    public function handle(Request $request): Response
    {
        return (new ResponseFactory())->notFound('Asset not found.');
    }
}
