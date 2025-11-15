<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorageUrl
{
    public static function publicUrl(string $relativePath, ?Request $request = null): string
    {
        $relativePath = ltrim($relativePath, '/');

        if (Str::startsWith($relativePath, 'storage/')) {
            $relativePath = Str::after($relativePath, 'storage/');
        }

        return self::baseUrl($request) . '/storage/' . $relativePath;
    }

    public static function normalize(?string $path, ?string $prefix = null): ?string
    {
        if (!$path) {
            return null;
        }

        $parsedPath = parse_url($path, PHP_URL_PATH) ?: $path;
        $clean = ltrim($parsedPath, '/');

        if (!Str::startsWith($clean, 'storage/')) {
            $segments = explode('/', $clean);
            $index = array_search('storage', $segments, true);
            if ($index !== false) {
                $clean = implode('/', array_slice($segments, $index));
            } elseif ($prefix && Str::startsWith($clean, $prefix)) {
                return $clean;
            }
        }

        if (!Str::startsWith($clean, 'storage/')) {
            return null;
        }

        $relative = Str::after($clean, 'storage/');

        if ($prefix && !Str::startsWith($relative, $prefix)) {
            return null;
        }

        return $relative;
    }

    private static function baseUrl(?Request $request = null): string
    {
        if (!$request && app()->runningInConsole()) {
            return rtrim(config('app.url'), '/');
        }

        $request = $request ?: request();

        if (!$request) {
            return rtrim(config('app.url'), '/');
        }

        $host = rtrim($request->getSchemeAndHttpHost(), '/');
        $basePath = trim($request->getBasePath(), '/');

        return $basePath ? $host . '/' . $basePath : $host;
    }
}
