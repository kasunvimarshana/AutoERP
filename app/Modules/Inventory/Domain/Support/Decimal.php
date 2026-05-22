<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Support;

final class Decimal
{
    private const SCALE = 4;

    public static function add(float $a, float $b): float
    {
        return round($a + $b, self::SCALE);
    }

    public static function sub(float $a, float $b): float
    {
        return round($a - $b, self::SCALE);
    }

    public static function mul(float $a, float $b): float
    {
        return round($a * $b, self::SCALE);
    }

    public static function div(float $a, float $b): float
    {
        if ($b == 0.0) {
            return 0.0;
        }

        return round($a / $b, self::SCALE);
    }

    public static function min(float $a, float $b): float
    {
        return $a < $b ? $a : $b;
    }
}
