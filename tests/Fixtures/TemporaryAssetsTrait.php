<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Fixtures;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait TemporaryAssetsTrait
{
    /**
     * @var list<string>
     */
    private array $temporaryAssetDirectories = [];

    protected function createTemporaryDirectory(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'comphp-assets-');

        if ($path === false) {
            self::fail('Unable to create a temporary path for asset tests.');
        }

        if (!unlink($path) || !mkdir($path, 0777, true)) {
            self::fail('Unable to create a temporary directory for asset tests.');
        }

        $this->temporaryAssetDirectories[] = $path;

        return $path;
    }

    protected function writeAssetFile(string $root, string $path, string $contents): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $file = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        $directory = dirname($file);

        if (!is_dir($directory) && !mkdir($directory, 0777, true)) {
            self::fail('Unable to create fixture asset directory.');
        }

        if (file_put_contents($file, $contents) === false) {
            self::fail('Unable to write fixture asset file.');
        }

        return $file;
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryAssetDirectories as $directory) {
            $this->removeTemporaryDirectory($directory);
        }

        $this->temporaryAssetDirectories = [];
    }

    private function removeTemporaryDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();

            if ($file->isDir()) {
                rmdir($path);
            } else {
                chmod($path, 0666);
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
