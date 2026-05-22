<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

final class ValuationMethod
{
    public const FIFO = 'FIFO';
    public const LIFO = 'LIFO';
    public const WEIGHTED_AVERAGE = 'WEIGHTED_AVERAGE';

    private function __construct()
    {
    }

    public static function normalize(?string $method, string $default = self::FIFO): string
    {
        if ($method === null || $method === '') {
            return $default;
        }

        return strtoupper(trim($method));
    }
}
