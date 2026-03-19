<?php

namespace Enterprise\Core\Math;

/**
 * EnterpriseMath - High-precision calculations wrapper using BCMath.
 * Ensures consistent precision across all microservices (default >= 4 decimals).
 */
class EnterpriseMath
{
    private static int $scale = 4;

    public static function setScale(int $scale) { self::$scale = $scale; }
    public static function getScale(): int { return self::$scale; }

    public static function add($a, $b, ?int $scale = null): string { return bcadd($a, $b, $scale ?? self::$scale); }
    public static function sub($a, $b, ?int $scale = null): string { return bcsub($a, $b, $scale ?? self::$scale); }
    public static function mul($a, $b, ?int $scale = null): string { return bcmul($a, $b, $scale ?? self::$scale); }
    public static function div($a, $b, ?int $scale = null): string { return bcdiv($a, $b, $scale ?? self::$scale); }
    public static function round($value, int $precision = 2): string {
        $multiplier = bcpow('10', (string)($precision + 1), 0);
        $scaled = bcmul((string)$value, $multiplier, 0);
        $lastDigit = substr($scaled, -1);
        $result = bcdiv($scaled, '10', 0);
        if ($lastDigit >= 5) { $result = bcadd($result, '1', 0); }
        return bcdiv($result, bcpow('10', (string)$precision, 0), $precision);
    }
}
