<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CurrencyFixture
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const CODE_LENGTH = 3;

    private const ALPHABET_LENGTH = 26;

    private const MAX_CODE_COUNT = self::ALPHABET_LENGTH ** self::CODE_LENGTH;

    private static int $sequence = 0;

    /**
     * Creates a schema-valid global currency for integration tests.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function create(array $attributes = []): int
    {
        $code = strtoupper(trim((string) ($attributes['code'] ?? self::nextCode())));
        if (preg_match('/^[A-Z]{3}$/D', $code) !== 1) {
            throw new RuntimeException('Currency test fixture requires a three-letter code.');
        }

        $now = now();

        return (int) DB::table('currencies')->insertGetId([
            'row_version' => max(1, (int) ($attributes['row_version'] ?? 1)),
            'code' => $code,
            'name' => $attributes['name'] ?? 'Test Currency '.$code,
            'symbol' => $attributes['symbol'] ?? $code,
            'decimal_places' => (int) ($attributes['decimal_places'] ?? 2),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'created_at' => $attributes['created_at'] ?? $now,
            'updated_at' => $attributes['updated_at'] ?? $now,
        ]);
    }

    public static function nextCode(): string
    {
        if (self::$sequence >= self::MAX_CODE_COUNT) {
            throw new RuntimeException('Currency test fixture exhausted its unique code range.');
        }

        $value = self::$sequence++;
        $code = '';
        for ($position = 0; $position < self::CODE_LENGTH; $position++) {
            $code = self::ALPHABET[$value % self::ALPHABET_LENGTH].$code;
            $value = intdiv($value, self::ALPHABET_LENGTH);
        }

        return $code;
    }

    private function __construct() {}
}
