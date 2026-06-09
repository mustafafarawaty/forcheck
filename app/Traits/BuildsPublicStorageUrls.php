<?php

namespace App\Traits;

trait BuildsPublicStorageUrls
{
    protected function publicStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $relativePath = '/storage/'.ltrim($path, '/');

        if (app()->bound('request')) {
            $origin = rtrim((string) app('request')->getSchemeAndHttpHost(), '/');

            if ($origin !== '') {
                return $origin.$relativePath;
            }
        }

        $fallbackOrigin = rtrim((string) config('app.url'), '/');

        return $fallbackOrigin !== ''
            ? $fallbackOrigin.$relativePath
            : $relativePath;
    }
}
