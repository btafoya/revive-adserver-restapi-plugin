<?php
namespace App\Support;

final class ReviveConfig
{
    public static function conf(): array { return $GLOBALS['_MAX']['CONF'] ?? []; }

    public static function table(string $name): string
    {
        $c = self::conf();
        $prefix = $c['table']['prefix'] ?? 'rv_';
        $map    = $c['table'] ?? [];
        $base   = $map[$name] ?? $name;
        return $prefix . $base;
    }

    public static function imagesDir(): string
    {
        $c = self::conf();
        return rtrim($c['store']['webDir'] ?? (__DIR__ . '/../../www/images'), '/');
    }

    public static function imagesUrlBase(): string
    {
        $c = self::conf();
        return rtrim($c['webpath']['imagesSSL'] ?? ($c['webpath']['images'] ?? '/www/images'), '/');
    }
}
