<?php

namespace App\Support;

use RuntimeException;

final class SecretFile
{
    public static function read(?string $path, ?string $fallback): ?string
    {
        if (! $path) {
            return $fallback;
        }

        $value = @file_get_contents($path);
        if ($value === false || trim($value) === '') {
            throw new RuntimeException('Cannot read a non-empty secret file.');
        }

        return rtrim($value, "\r\n");
    }
}
