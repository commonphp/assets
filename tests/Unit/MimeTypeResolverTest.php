<?php

declare(strict_types=1);

namespace CommonPHP\Assets\Tests\Unit;

use CommonPHP\Assets\Contracts\MimeTypeResolverInterface;
use CommonPHP\Assets\MimeTypeResolver;
use PHPUnit\Framework\TestCase;

final class MimeTypeResolverTest extends TestCase
{
    public function testItImplementsTheContractAndResolvesDefaultTypes(): void
    {
        $resolver = new MimeTypeResolver(useFinfo: false);

        self::assertInstanceOf(MimeTypeResolverInterface::class, $resolver);
        self::assertSame('text/css; charset=utf-8', $resolver->resolve('css/app.css'));
        self::assertSame('text/javascript; charset=utf-8', $resolver->resolve('/js/app.mjs?version=1'));
        self::assertSame('image/svg+xml', $resolver->resolve('icons/logo.svg'));
        self::assertSame('font/woff2', $resolver->resolve('fonts/inter.woff2'));
        self::assertSame('application/wasm', $resolver->resolve('pkg/module.wasm'));
    }

    public function testCustomTypesOverrideDefaults(): void
    {
        $resolver = new MimeTypeResolver(['css' => 'application/x-css'], useFinfo: false);

        self::assertSame('application/x-css', $resolver->resolve('app.css'));
    }

    public function testWithTypeReturnsACloneWithTheAdditionalType(): void
    {
        $resolver = new MimeTypeResolver(useFinfo: false);
        $custom = $resolver->withType('.webmanifest', 'application/manifest+json');

        self::assertNotSame($resolver, $custom);
        self::assertSame('application/octet-stream', $resolver->resolve('site.webmanifest'));
        self::assertSame('application/manifest+json', $custom->resolve('site.webmanifest'));
    }

    public function testUnknownTypesUseTheFallbackWhenFinfoIsDisabled(): void
    {
        $resolver = new MimeTypeResolver(fallback: 'text/plain', useFinfo: false);

        self::assertSame('text/plain', $resolver->resolve('asset.unknown-extension'));
    }

    public function testItExposesNormalizedTypeMap(): void
    {
        $resolver = new MimeTypeResolver(['.CUSTOM' => 'application/x-custom'], useFinfo: false);

        self::assertSame('application/x-custom', $resolver->types()['custom']);
    }
}
