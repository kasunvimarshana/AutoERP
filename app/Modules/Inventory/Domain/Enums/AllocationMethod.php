<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

final class AllocationMethod
{
    public const QUANTITY = 'QUANTITY';
    public const BATCH = 'BATCH';
    public const LOT = 'LOT';

    private function __construct()
    {
    }

    public static function normalize(?string $method, string $default = self::QUANTITY): string
    {
        if ($method === null || $method === '') {
            return $default;
        }

        return strtoupper(trim($method));
    }
}
