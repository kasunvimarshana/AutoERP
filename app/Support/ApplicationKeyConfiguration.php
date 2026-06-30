<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Encryption\Encrypter;

final class ApplicationKeyConfiguration
{
    public const BASE64_PREFIX = 'base64:';

    /** @return list<string> */
    public static function values(string $environmentContents): array
    {
        preg_match_all('/^APP_KEY=(.*)$/m', $environmentContents, $matches);

        return array_map(
            static fn (string $raw): string => self::stripOptionalQuotes(trim($raw)),
            $matches[1] ?? [],
        );
    }

    public static function decode(string $value): string
    {
        $value = trim($value);
        if (! str_starts_with($value, self::BASE64_PREFIX)) {
            return $value;
        }

        $decoded = base64_decode(substr($value, strlen(self::BASE64_PREFIX)), true);

        return is_string($decoded) ? $decoded : '';
    }

    public static function isValid(string $value, string $cipher): bool
    {
        $decoded = self::decode($value);

        return $decoded !== '' && Encrypter::supported($decoded, $cipher);
    }

    public static function fingerprint(string $value, int $length): string
    {
        return substr(hash('sha256', self::decode($value)), 0, $length);
    }

    private static function stripOptionalQuotes(string $value): string
    {
        if (strlen($value) < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            return trim(substr($value, 1, -1));
        }

        return $value;
    }
}
