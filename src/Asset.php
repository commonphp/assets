<?php

declare(strict_types=1);

namespace CommonPHP\Assets;

use CommonPHP\Assets\Contracts\MimeTypeResolverInterface;
use CommonPHP\Assets\Enums\AssetType;
use CommonPHP\Assets\Exceptions\UnreadableAssetException;
use Stringable;

final readonly class Asset implements Stringable
{
    public function __construct(
        private string $path,
        private string $realPath,
        private string $mimeType,
        private int $size,
        private int $modifiedAt,
        private AssetType $type = AssetType::Other,
    ) {
    }

    public static function fromFile(
        string $path,
        string $realPath,
        ?MimeTypeResolverInterface $mimeTypes = null,
    ): self {
        $size = filesize($realPath);
        $modifiedAt = filemtime($realPath);

        if ($size === false || $modifiedAt === false) {
            throw UnreadableAssetException::forPath($path);
        }

        $mimeTypes ??= new MimeTypeResolver();
        $mimeType = $mimeTypes->resolve($realPath);

        return new self(
            $path,
            $realPath,
            $mimeType,
            $size,
            $modifiedAt,
            AssetType::fromPath($path, $mimeType),
        );
    }

    public function path(): string
    {
        return $this->path;
    }

    public function realPath(): string
    {
        return $this->realPath;
    }

    public function filename(): string
    {
        return basename($this->path);
    }

    public function extension(): ?string
    {
        $extension = pathinfo($this->path, PATHINFO_EXTENSION);

        return $extension === '' ? null : strtolower($extension);
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function modifiedAt(): int
    {
        return $this->modifiedAt;
    }

    public function type(): AssetType
    {
        return $this->type;
    }

    public function etag(): string
    {
        return '"' . sha1($this->path . '|' . $this->size . '|' . $this->modifiedAt) . '"';
    }

    public function lastModifiedHeader(): string
    {
        return gmdate('D, d M Y H:i:s', $this->modifiedAt) . ' GMT';
    }

    public function contents(): string
    {
        $contents = @file_get_contents($this->realPath);

        if (!is_string($contents)) {
            throw UnreadableAssetException::forPath($this->path);
        }

        return $contents;
    }

    /**
     * @return array{
     *     path: string,
     *     realPath: string,
     *     filename: string,
     *     extension: string|null,
     *     mimeType: string,
     *     size: int,
     *     modifiedAt: int,
     *     type: string,
     *     etag: string
     * }
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'realPath' => $this->realPath,
            'filename' => $this->filename(),
            'extension' => $this->extension(),
            'mimeType' => $this->mimeType,
            'size' => $this->size,
            'modifiedAt' => $this->modifiedAt,
            'type' => $this->type->value,
            'etag' => $this->etag(),
        ];
    }

    public function __toString(): string
    {
        return $this->path;
    }

}
