<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;

final class InvoiceAmountInWordsFormatter
{
    private const SCALE = 2;

    /** @var array<int, string> */
    private const SMALL = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven',
        12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
        17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
    ];

    /** @var array<int, string> */
    private const TENS = [
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
        60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety',
    ];

    /** @var array<int, string> */
    private const GROUPS = ['', 'Thousand', 'Million', 'Billion', 'Trillion', 'Quadrillion'];

    public function format(mixed $amount, ?string $currencyCode): string
    {
        $decimal = trim((string) $amount);
        if (preg_match('/^-?\d+(?:\.\d+)?$/D', $decimal) !== 1) {
            throw new InvalidArgumentException('Invoice total must be a plain decimal value.');
        }

        $rounded = bcadd(
            $decimal,
            str_starts_with($decimal, '-') ? '-0.005' : '0.005',
            self::SCALE,
        );
        $negative = str_starts_with($rounded, '-');
        $absolute = ltrim($rounded, '-');
        [$whole, $fraction] = array_pad(explode('.', $absolute, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 18) {
            throw new InvalidArgumentException('Invoice total is too large to format in words.');
        }

        $currency = strtoupper(trim((string) $currencyCode));
        $prefix = $currency === '' ? '' : $currency.' ';
        $words = ($negative ? 'Minus ' : '').$this->wholeNumber($whole);
        $minor = str_pad(substr($fraction, 0, self::SCALE), self::SCALE, '0');

        return $prefix.$words.' and '.$minor.'/100 Only';
    }

    private function wholeNumber(string $digits): string
    {
        if ($digits === '0') {
            return self::SMALL[0];
        }

        $groups = [];
        while ($digits !== '') {
            $groups[] = (int) substr($digits, -3);
            $digits = substr($digits, 0, max(0, strlen($digits) - 3));
        }

        $parts = [];
        for ($index = count($groups) - 1; $index >= 0; $index--) {
            $value = $groups[$index];
            if ($value === 0) {
                continue;
            }
            $part = $this->underThousand($value);
            $group = self::GROUPS[$index] ?? null;
            if ($group === null) {
                throw new InvalidArgumentException('Invoice total is too large to format in words.');
            }
            $parts[] = trim($part.' '.$group);
        }

        return implode(' ', $parts);
    }

    private function underThousand(int $value): string
    {
        $parts = [];
        if ($value >= 100) {
            $parts[] = self::SMALL[intdiv($value, 100)].' Hundred';
            $value %= 100;
        }
        if ($value >= 20) {
            $tens = intdiv($value, 10) * 10;
            $parts[] = self::TENS[$tens].($value % 10 === 0 ? '' : '-'.self::SMALL[$value % 10]);
        } elseif ($value > 0) {
            $parts[] = self::SMALL[$value];
        }

        return implode(' ', $parts);
    }
}
