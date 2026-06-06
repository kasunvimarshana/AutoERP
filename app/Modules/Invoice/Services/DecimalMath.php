<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;

final class DecimalMath
{
    public const SCALE = 6;

    public function normalize(int|string|null $value, int $scale = self::SCALE): string
    {
        $value = trim((string) ($value ?? '0'));
        if ($value === '') {
            $value = '0';
        }

        if (! preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Decimal values must be plain integer or decimal strings.');
        }

        $negative = str_starts_with($value, '-');
        $unsigned = $negative ? substr($value, 1) : $value;
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = substr(str_pad($fraction, $scale, '0'), 0, $scale);
        $normalized = $whole.'.'.$fraction;

        if ($negative && ! $this->isZero($normalized)) {
            return '-'.$normalized;
        }

        return $normalized;
    }

    public function add(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        if (function_exists('bcadd')) {
            return $this->normalize(bcadd($this->normalize($left, $scale), $this->normalize($right, $scale), $scale), $scale);
        }

        return $this->fromScaledInt($this->toScaledInt($left, $scale) + $this->toScaledInt($right, $scale), $scale);
    }

    public function sub(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        if (function_exists('bcsub')) {
            return $this->normalize(bcsub($this->normalize($left, $scale), $this->normalize($right, $scale), $scale), $scale);
        }

        return $this->fromScaledInt($this->toScaledInt($left, $scale) - $this->toScaledInt($right, $scale), $scale);
    }

    public function mul(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        if (function_exists('bcmul')) {
            return $this->normalize(bcmul($this->normalize($left, $scale), $this->normalize($right, $scale), $scale), $scale);
        }

        $factor = 10 ** $scale;

        return $this->fromScaledInt(
            intdiv($this->toScaledInt($left, $scale) * $this->toScaledInt($right, $scale), $factor),
            $scale,
        );
    }

    public function div(int|string $left, int|string $right, int $scale = self::SCALE): string
    {
        if ($this->compare($right, '0', $scale) === 0) {
            throw new InvalidArgumentException('Cannot divide by zero.');
        }

        if (function_exists('bcdiv')) {
            return $this->normalize(bcdiv($this->normalize($left, $scale), $this->normalize($right, $scale), $scale), $scale);
        }

        return $this->fromScaledInt(
            intdiv($this->toScaledInt($left, $scale) * (10 ** $scale), $this->toScaledInt($right, $scale)),
            $scale,
        );
    }

    public function compare(int|string $left, int|string $right, int $scale = self::SCALE): int
    {
        if (function_exists('bccomp')) {
            return bccomp($this->normalize($left, $scale), $this->normalize($right, $scale), $scale);
        }

        return $this->toScaledInt($left, $scale) <=> $this->toScaledInt($right, $scale);
    }

    public function isNegative(int|string $value): bool
    {
        return $this->compare($value, '0') < 0;
    }

    public function isZero(int|string $value): bool
    {
        return preg_replace('/[.\-0]/', '', $this->normalize($value)) === '';
    }

    public function sum(array $values): string
    {
        $total = '0.000000';
        foreach ($values as $value) {
            $total = $this->add($total, $this->normalize((string) $value));
        }

        return $total;
    }

    private function toScaledInt(int|string $value, int $scale): int
    {
        $normalized = $this->normalize($value, $scale);
        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$whole, $fraction] = explode('.', $unsigned, 2);
        $integer = ((int) $whole * (10 ** $scale)) + (int) $fraction;

        return $negative ? -$integer : $integer;
    }

    private function fromScaledInt(int $value, int $scale): string
    {
        $negative = $value < 0;
        $value = abs($value);
        $factor = 10 ** $scale;
        $whole = intdiv($value, $factor);
        $fraction = str_pad((string) ($value % $factor), $scale, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }
}
