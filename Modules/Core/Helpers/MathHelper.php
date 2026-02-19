<?php

declare(strict_types=1);

namespace Modules\Core\Helpers;

/**
 * MathHelper
 *
 * Precision-safe mathematical operations using BCMath
 * All financial and quantity calculations must use this helper
 */
class MathHelper
{
    /**
     * Default decimal scale
     */
    protected static int $defaultScale = 6;

    /**
     * Set default scale
     */
    public static function setDefaultScale(int $scale): void
    {
        static::$defaultScale = $scale;
    }

    /**
     * Get default scale
     */
    public static function getDefaultScale(): int
    {
        return static::$defaultScale;
    }

    /**
     * Add two numbers
     */
    public static function add(string $left, string $right, ?int $scale = null): string
    {
        return bcadd($left, $right, $scale ?? static::$defaultScale);
    }

    /**
     * Subtract two numbers
     */
    public static function subtract(string $left, string $right, ?int $scale = null): string
    {
        return bcsub($left, $right, $scale ?? static::$defaultScale);
    }

    /**
     * Multiply two numbers
     */
    public static function multiply(string $left, string $right, ?int $scale = null): string
    {
        return bcmul($left, $right, $scale ?? static::$defaultScale);
    }

    /**
     * Divide two numbers
     */
    public static function divide(string $dividend, string $divisor, ?int $scale = null): string
    {
        if (bccomp($divisor, '0', $scale ?? static::$defaultScale) === 0) {
            throw new \InvalidArgumentException('Division by zero');
        }

        return bcdiv($dividend, $divisor, $scale ?? static::$defaultScale);
    }

    /**
     * Calculate percentage
     */
    public static function percentage(string $value, string $percentage, ?int $scale = null): string
    {
        $scale = $scale ?? static::$defaultScale;
        $result = bcmul($value, $percentage, $scale);

        return bcdiv($result, '100', $scale);
    }

    /**
     * Round to specified decimals
     */
    public static function round(string $value, int $decimals = 2): string
    {
        $scale = $decimals + 1;
        $rounded = bcadd($value, '0', $scale);

        return bcadd($rounded, '0', $decimals);
    }

    /**
     * Compare two numbers
     *
     * @return int -1 if left < right, 0 if equal, 1 if left > right
     */
    public static function compare(string $left, string $right, ?int $scale = null): int
    {
        return bccomp($left, $right, $scale ?? static::$defaultScale);
    }

    /**
     * Check if numbers are equal
     */
    public static function equals(string $left, string $right, ?int $scale = null): bool
    {
        return static::compare($left, $right, $scale) === 0;
    }

    /**
     * Check if left is greater than right
     */
    public static function greaterThan(string $left, string $right, ?int $scale = null): bool
    {
        return static::compare($left, $right, $scale) === 1;
    }

    /**
     * Check if left is less than right
     */
    public static function lessThan(string $left, string $right, ?int $scale = null): bool
    {
        return static::compare($left, $right, $scale) === -1;
    }

    /**
     * Get minimum of two numbers
     */
    public static function min(string $left, string $right, ?int $scale = null): string
    {
        return static::lessThan($left, $right, $scale) ? $left : $right;
    }

    /**
     * Get maximum of two numbers
     */
    public static function max(string $left, string $right, ?int $scale = null): string
    {
        return static::greaterThan($left, $right, $scale) ? $left : $right;
    }

    /**
     * Get absolute value
     */
    public static function abs(string $value, ?int $scale = null): string
    {
        if (static::lessThan($value, '0', $scale)) {
            return bcmul($value, '-1', $scale ?? static::$defaultScale);
        }

        return $value;
    }

    /**
     * Sum an array of values
     */
    public static function sum(array $values, ?int $scale = null): string
    {
        $result = '0';
        foreach ($values as $value) {
            $result = static::add($result, (string) $value, $scale);
        }

        return $result;
    }

    /**
     * Format for display
     */
    public static function format(string $value, int $decimals = 2, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        $rounded = static::round($value, $decimals);

        return number_format((float) $rounded, $decimals, $decimalSeparator, $thousandsSeparator);
    }
}
