<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Exceptions\AssetException;
use CommonPHP\Assets\Exceptions\AssetNotFoundException;
use CommonPHP\Assets\Exceptions\InvalidAssetPathException;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

class AssetSurface implements HttpSurfaceInterface
{
    private AssetManager $assets;

    private string $pathPrefix;

    public function __construct(?AssetManager $assets = null, string $pathPrefix = '/assets')
    {
        $this->assets = $assets ?? new AssetManager();
        $this->pathPrefix = AssetRequest::normalizeMountPath($pathPrefix);
    }

    public function supports(Request $request): bool
    {
        return $this->pathPrefix === '/'
            || $request->path() === $this->pathPrefix
            || str_starts_with($request->path(), $this->pathPrefix . '/');
    }

    public function handle(Request $request): Response
    {
        if (!in_array($request->method(), [RequestMethod::GET, RequestMethod::HEAD], true)) {
            return new Response(
                'Method Not Allowed',
                ResponseStatus::METHOD_NOT_ALLOWED,
                ['Allow' => 'GET, HEAD', 'Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        try {
            $assetRequest = AssetRequest::fromRequest($request, $this->pathPrefix);

            if (!$assetRequest->hasAssetPath()) {
                return $this->notFound();
            }

            return $this->assets->response($assetRequest, $request);
        } catch (AssetNotFoundException) {
            return $this->notFound();
        } catch (InvalidAssetPathException) {
            return new Response('Invalid asset path.', ResponseStatus::BAD_REQUEST, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        } catch (UnreadableAssetException) {
            return new Response('Asset is not readable.', ResponseStatus::FORBIDDEN, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        } catch (AssetException) {
            return new Response('Unable to serve asset.', ResponseStatus::INTERNAL_SERVER_ERROR, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }
    }

    public function manager(): AssetManager
    {
        return $this->assets;
    }

    public function pathPrefix(): string
    {
        return $this->pathPrefix;
    }

    private function notFound(): Response
    {
        return new Response('Asset not found.', ResponseStatus::NOT_FOUND, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
