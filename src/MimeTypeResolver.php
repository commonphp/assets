<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Contracts\MimeTypeResolverInterface;

class MimeTypeResolver implements MimeTypeResolverInterface
{
    /**
     * @var array<string, string>
     */
    private array $types;

    /**
     * @param array<string, string> $types
     */
    public function __construct(
        array $types = [],
        private readonly string $fallback = 'application/octet-stream',
        private readonly bool $useFinfo = true,
    ) {
        $this->types = array_replace(self::defaultTypes(), $this->normalizeTypes($types));
    }

    public function resolve(string $path): string
    {
        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));

        if ($extension !== '' && isset($this->types[$extension])) {
            return $this->types[$extension];
        }

        if ($this->useFinfo && is_file($path) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $path);

                if (is_string($mimeType) && $mimeType !== '') {
                    return $mimeType;
                }
            }
        }

        return $this->fallback;
    }

    public function withType(string $extension, string $mimeType): self
    {
        $clone = clone $this;
        $clone->types[$this->normalizeExtension($extension)] = trim($mimeType);

        return $clone;
    }

    /**
     * @return array<string, string>
     */
    public function types(): array
    {
        return $this->types;
    }

    /**
     * @return array<string, string>
     */
    public static function defaultTypes(): array
    {
        return [
            'aac' => 'audio/aac',
            'apng' => 'image/apng',
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            'css' => 'text/css; charset=utf-8',
            'csv' => 'text/csv; charset=utf-8',
            'eot' => 'application/vnd.ms-fontobject',
            'gif' => 'image/gif',
            'htm' => 'text/html; charset=utf-8',
            'html' => 'text/html; charset=utf-8',
            'ico' => 'image/x-icon',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'text/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'map' => 'application/json; charset=utf-8',
            'md' => 'text/markdown; charset=utf-8',
            'mjs' => 'text/javascript; charset=utf-8',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'oga' => 'audio/ogg',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'otf' => 'font/otf',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'toml' => 'application/toml; charset=utf-8',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain; charset=utf-8',
            'wasm' => 'application/wasm',
            'wav' => 'audio/wav',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'xhtml' => 'application/xhtml+xml; charset=utf-8',
            'xml' => 'application/xml; charset=utf-8',
            'yaml' => 'application/yaml; charset=utf-8',
            'yml' => 'application/yaml; charset=utf-8',
        ];
    }

    /**
     * @param array<string, string> $types
     *
     * @return array<string, string>
     */
    private function normalizeTypes(array $types): array
    {
        $normalized = [];

        foreach ($types as $extension => $mimeType) {
            $normalized[$this->normalizeExtension((string) $extension)] = trim($mimeType);
        }

        return $normalized;
    }

    private function normalizeExtension(string $extension): string
    {
        return strtolower(ltrim(trim($extension), '.'));
    }

}
