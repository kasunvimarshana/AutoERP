<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

final class StockDirection
{
    public const IN = 'IN';
    public const OUT = 'OUT';

    private function __construct()
    {
    }

    public static function normalize(string $direction): string
    {
        return strtoupper(trim($direction));
    }
}
