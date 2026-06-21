<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use InvalidArgumentException;
use RuntimeException;

/**
 * Exact base-10 arithmetic for persisted DECIMAL values and API decimal strings.
 *
 * Inputs and outputs are plain decimal strings. Binary floating-point values,
 * scientific notation and silent precision loss are intentionally unsupported.
 * Arithmetic methods quantize their result to the requested scale using BCMath's
 * truncation semantics; business-specific rounding belongs to the owning module.
 */
final class DecimalMath
{
    public const SCALE = 6;

    public const MAX_SCALE = 18;

    public const MAX_INTEGER_DIGITS = 38;

    private const DECIMAL_PATTERN = '/^-?\d+(?:\.\d+)?$/D';

    public function __construct()
    {
        if (! extension_loaded('bcmath')) {
            throw new RuntimeException('The BCMath PHP extension is required for exact decimal arithmetic.');
        }
    }

    /**
     * Returns a fixed-scale decimal without discarding non-zero precision.
     */
    public function normalize(int|string $value, int $scale = self::SCALE): string
    {
        $this->assertScale($scale);
        $canonical = $this->canonical($value);
        $negative = str_starts_with($canonical, '-');
        $unsigned = $negative ? substr($canonical, 1) : $canonical;
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        if (strlen($fraction) > $scale) {
            $discarded = substr($fraction, $scale);
            if (trim($discarded, '0') !== '') {
                throw new InvalidArgumentException(sprintf(
                    'Decimal value exceeds the supported scale of %d without an explicit rounding rule.',
                    $scale,
                ));
            }

            $fraction = substr($fraction, 0, $scale);
        }

        $fraction = str_pad($fraction, $scale, '0');
        $normalized = $scale === 0 ? $whole : $whole.'.'.$fraction;

        return $negative && ! $this->isExactlyZero($normalized) ? '-'.$normalized : $normalized;
    }

    public function add(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        $this->assertScale($scale);

        return $this->normalize(
            bcadd($this->canonical($left), $this->canonical($right), $scale),
            $scale,
        );
    }

    public function sub(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        $this->assertScale($scale);

        return $this->normalize(
            bcsub($this->canonical($left), $this->canonical($right), $scale),
            $scale,
        );
    }

    public function mul(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        $this->assertScale($scale);

        return $this->normalize(
            bcmul($this->canonical($left), $this->canonical($right), $scale),
            $scale,
        );
    }

    public function div(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        $this->assertScale($scale);
        $right = $this->canonical($right);

        if ($this->isExactlyZero($right)) {
            throw new InvalidArgumentException('Cannot divide by zero.');
        }

        return $this->normalize(
            bcdiv($this->canonical($left), $right, $scale),
            $scale,
        );
    }

    public function compare(int|string $left, int|string $right, int $scale = self::SCALE): int
    {
        $this->assertScale($scale);

        return bccomp($this->canonical($left), $this->canonical($right), $scale);
    }

    public function isNegative(int|string $value, int $scale = self::SCALE): bool
    {
        return $this->compare($value, '0', $scale) < 0;
    }

    public function isZero(int|string $value, int $scale = self::SCALE): bool
    {
        return $this->compare($value, '0', $scale) === 0;
    }

    /**
     * @param  iterable<int|string>  $values
     */
    public function sum(iterable $values, int $scale = self::SCALE): string
    {
        $this->assertScale($scale);
        $total = $this->normalize('0', $scale);

        foreach ($values as $value) {
            $total = $this->add($total, $value, $scale);
        }

        return $total;
    }

    private function canonical(int|string $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '' || preg_match(self::DECIMAL_PATTERN, $normalized) !== 1) {
            throw new InvalidArgumentException('Decimal values must be plain integer or decimal strings.');
        }

        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');

        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($fraction, '0');

        if (strlen($whole) > self::MAX_INTEGER_DIGITS || strlen($fraction) > self::MAX_SCALE) {
            throw new InvalidArgumentException(sprintf(
                'Decimal values support at most %d integer digits and %d fractional digits.',
                self::MAX_INTEGER_DIGITS,
                self::MAX_SCALE,
            ));
        }

        $canonical = $fraction === '' ? $whole : $whole.'.'.$fraction;

        return $negative && ! $this->isExactlyZero($canonical) ? '-'.$canonical : $canonical;
    }

    private function assertScale(int $scale): void
    {
        if ($scale < 0 || $scale > self::MAX_SCALE) {
            throw new InvalidArgumentException(sprintf(
                'Decimal scale must be between 0 and %d.',
                self::MAX_SCALE,
            ));
        }
    }

    private function isExactlyZero(string $value): bool
    {
        return trim(str_replace(['-', '.', '0'], '', $value)) === '';
    }
}
