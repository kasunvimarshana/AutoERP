<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use InvalidArgumentException;

final class AmountInWordsService
{
    private const SMALL = [
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
    ];

    private const TENS = [
        2 => 'twenty',
        3 => 'thirty',
        4 => 'forty',
        5 => 'fifty',
        6 => 'sixty',
        7 => 'seventy',
        8 => 'eighty',
        9 => 'ninety',
    ];

    private const SCALES = ['', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion'];

    public function convert(string $amount): string
    {
        $normalized = trim(str_replace(',', '', $amount));
        if (! preg_match('/^\d+(?:\.\d+)?$/', $normalized)) {
            throw new InvalidArgumentException('Amount must be a non-negative decimal value.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $whole = ltrim($whole, '0');
        $wholeWords = $this->wholeNumberWords($whole === '' ? '0' : $whole);
        $cents = (int) str_pad(substr($fraction, 0, 2), 2, '0');
        $words = $wholeWords;

        if ($cents > 0) {
            $words .= ' and '.$this->underThousandWords($cents).' cents';
        }

        return ucwords($words).' Only';
    }

    private function wholeNumberWords(string $number): string
    {
        if ($number === '0') {
            return self::SMALL[0];
        }

        $groups = array_reverse(str_split(str_pad($number, (int) ceil(strlen($number) / 3) * 3, '0', STR_PAD_LEFT), 3));
        if (count($groups) > count(self::SCALES)) {
            throw new InvalidArgumentException('Amount is too large to convert to words.');
        }

        $parts = [];
        foreach ($groups as $index => $group) {
            $value = (int) $group;
            if ($value === 0) {
                continue;
            }

            $part = $this->underThousandWords($value);
            if (self::SCALES[$index] !== '') {
                $part .= ' '.self::SCALES[$index];
            }
            array_unshift($parts, $part);
        }

        return implode(' ', $parts);
    }

    private function underThousandWords(int $number): string
    {
        $parts = [];
        if ($number >= 100) {
            $parts[] = self::SMALL[intdiv($number, 100)].' hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $parts[] = self::TENS[intdiv($number, 10)].($number % 10 > 0 ? '-'.self::SMALL[$number % 10] : '');
        } elseif ($number > 0) {
            $parts[] = self::SMALL[$number];
        }

        return implode(' ', $parts);
    }
}
