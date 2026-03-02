<?php

declare(strict_types=1);

namespace Modules\Core\Application\Helpers;

use InvalidArgumentException;

/**
 * BCMath Decimal precision helper.
 *
 * All financial and quantity calculations MUST use this class.
 * Floating-point arithmetic is strictly forbidden.
 *
 * Precision standards (per AGENT.md):
 *   - Standard calculations:       minimum 4 decimal places
 *   - Intermediate calculations:   8+ decimal places
 *   - Final monetary values:       2 decimal places (currency standard)
 */
final class DecimalHelper
{
    public const SCALE_STANDARD = 4;

    public const SCALE_INTERMEDIATE = 8;

    public const SCALE_MONETARY = 2;

    /**
     * Add two arbitrary-precision decimal strings.
     */
    public static function add(string $a, string $b, int $scale = self::SCALE_STANDARD): string
    {
        self::assertNumeric($a, 'a');
        self::assertNumeric($b, 'b');

        return bcadd($a, $b, $scale);
    }

    /**
     * Subtract $b from $a.
     */
    public static function sub(string $a, string $b, int $scale = self::SCALE_STANDARD): string
    {
        self::assertNumeric($a, 'a');
        self::assertNumeric($b, 'b');

        return bcsub($a, $b, $scale);
    }

    /**
     * Multiply two arbitrary-precision decimal strings.
     */
    public static function mul(string $a, string $b, int $scale = self::SCALE_INTERMEDIATE): string
    {
        self::assertNumeric($a, 'a');
        self::assertNumeric($b, 'b');

        return bcmul($a, $b, $scale);
    }

    /**
     * Divide $a by $b.
     *
     * @throws \DivisionByZeroError
     */
    public static function div(string $a, string $b, int $scale = self::SCALE_INTERMEDIATE): string
    {
        self::assertNumeric($a, 'a');
        self::assertNumeric($b, 'b');

        if (bccomp($b, '0', self::SCALE_INTERMEDIATE) === 0) {
            throw new \DivisionByZeroError('Division by zero is not allowed.');
        }

        return bcdiv($a, $b, $scale);
    }

    /**
     * Modulo operation.
     */
    public static function mod(string $a, string $b, int $scale = self::SCALE_STANDARD): string
    {
        self::assertNumeric($a, 'a');
        self::assertNumeric($b, 'b');

        if (bccomp($b, '0', self::SCALE_INTERMEDIATE) === 0) {
            throw new \DivisionByZeroError('Modulo by zero is not allowed.');
        }

        return bcmod($a, $b, $scale);
    }

    /**
     * Raise $base to the power $exponent (integer exponent only).
     */
    public static function pow(string $base, int $exponent, int $scale = self::SCALE_STANDARD): string
    {
        self::assertNumeric($base, 'base');

        return bcpow($base, (string) $exponent, $scale);
    }

    /**
     * Round a decimal string to the given scale using "round half away from zero".
     */
    public static function round(string $value, int $scale = self::SCALE_MONETARY): string
    {
        self::assertNumeric($value, 'value');

        if ($scale < 0) {
            throw new InvalidArgumentException('Scale must be >= 0.');
        }

        $factor = bcpow('10', (string) $scale, 0);

        if (bccomp($value, '0', self::SCALE_INTERMEDIATE) >= 0) {
            $rounded = bcdiv(
                bcadd(bcmul($value, $factor, self::SCALE_INTERMEDIATE), '0.5', self::SCALE_INTERMEDIATE),
                $factor,
                $scale
            );
        } else {
            $rounded = bcdiv(
                bcsub(bcmul($value, $factor, self::SCALE_INTERMEDIATE), '0.5', self::SCALE_INTERMEDIATE),
                $factor,
                $scale
            );
        }

        return $rounded;
    }

    /**
     * Round a value to the standard monetary scale (2 decimal places).
     */
    public static function toMonetary(string $value): string
    {
        return self::round($value, self::SCALE_MONETARY);
    }

    /**
     * Compare two decimal strings.
     *
     * Returns -1, 0, or 1.
     */
    public static function compare(string $a, string $b, int $scale = self::SCALE_STANDARD): int
    {
        self::assertNumeric($a, 'a');
        self::assertNumeric($b, 'b');

        return bccomp($a, $b, $scale);
    }

    /**
     * Return true if $a equals $b.
     */
    public static function equals(string $a, string $b, int $scale = self::SCALE_STANDARD): bool
    {
        return self::compare($a, $b, $scale) === 0;
    }

    /**
     * Return true if $a is greater than $b.
     */
    public static function greaterThan(string $a, string $b, int $scale = self::SCALE_STANDARD): bool
    {
        return self::compare($a, $b, $scale) === 1;
    }

    /**
     * Return true if $a is less than $b.
     */
    public static function lessThan(string $a, string $b, int $scale = self::SCALE_STANDARD): bool
    {
        return self::compare($a, $b, $scale) === -1;
    }

    /**
     * Return true if $a is greater than or equal to $b.
     */
    public static function greaterThanOrEqual(string $a, string $b, int $scale = self::SCALE_STANDARD): bool
    {
        return self::compare($a, $b, $scale) >= 0;
    }

    /**
     * Return true if $a is less than or equal to $b.
     */
    public static function lessThanOrEqual(string $a, string $b, int $scale = self::SCALE_STANDARD): bool
    {
        return self::compare($a, $b, $scale) <= 0;
    }

    /**
     * Return the absolute value of a decimal string.
     */
    public static function abs(string $value): string
    {
        self::assertNumeric($value, 'value');

        return ltrim($value, '-');
    }

    /**
     * Convert an integer to a BCMath-safe string.
     */
    public static function fromInt(int $value): string
    {
        return (string) $value;
    }

    /**
     * Assert that a string is a valid numeric decimal.
     *
     * @throws InvalidArgumentException
     */
    private static function assertNumeric(string $value, string $paramName): void
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                "DecimalHelper: parameter '{$paramName}' must be a numeric string, got: '{$value}'"
            );
        }
    }
}
