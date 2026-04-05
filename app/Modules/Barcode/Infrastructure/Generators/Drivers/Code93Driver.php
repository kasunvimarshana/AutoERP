<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Code 93 driver.
 *
 * Encodes uppercase A–Z, digits 0–9, and the special characters: space - . $ / + %
 * (43 characters in total, values 0–42).
 *
 * Each symbol is represented by 3 bars and 3 spaces (6 elements alternating B/S/B/S/B/S)
 * where each element is 1–4 modules wide and the total is always exactly 9 modules.
 *
 * Two modulo-47 check characters (C then K) are automatically appended before the stop bar.
 * Position weights cycle through 1–20 from right to left (per ISO/IEC 16388).
 */
class Code93Driver extends AbstractSvgBarcodeDriver
{
    /**
     * Encoding table for the 43 basic characters.
     * Each entry: [b1, s1, b2, s2, b3, s3] module widths (sum = 9).
     *
     * @var array<string, int[]>
     */
    private const PATTERNS = [
        '0' => [1,3,1,1,1,2],
        '1' => [1,1,1,2,1,3],
        '2' => [1,1,1,3,1,2],
        '3' => [1,1,1,4,1,1],
        '4' => [1,2,1,1,1,3],
        '5' => [1,2,1,2,1,2],
        '6' => [1,2,1,3,1,1],
        '7' => [1,1,1,1,1,4],
        '8' => [1,3,1,2,1,1],
        '9' => [1,4,1,1,1,1],
        'A' => [2,1,1,1,1,3],
        'B' => [2,1,1,2,1,2],
        'C' => [2,1,1,3,1,1],
        'D' => [2,2,1,1,1,2],
        'E' => [2,2,1,2,1,1],
        'F' => [2,3,1,1,1,1],
        'G' => [1,1,2,1,1,3],
        'H' => [1,1,2,2,1,2],
        'I' => [1,1,2,3,1,1],
        'J' => [1,2,2,1,1,2],
        'K' => [1,3,2,1,1,1],
        'L' => [1,1,1,1,2,3],
        'M' => [1,1,1,2,2,2],
        'N' => [1,1,1,3,2,1],
        'O' => [1,2,1,1,2,2],
        'P' => [1,3,1,1,2,1],
        'Q' => [2,1,2,1,1,2],
        'R' => [2,1,2,2,1,1],
        'S' => [2,1,1,1,2,2],
        'T' => [2,1,1,2,2,1],
        'U' => [2,2,2,1,1,1],
        'V' => [2,2,1,1,2,1],
        'W' => [1,1,2,1,2,2],
        'X' => [1,1,2,2,2,1],
        'Y' => [1,2,2,1,2,1],
        'Z' => [1,2,3,1,1,1],
        '-' => [1,2,1,1,3,1],
        '.' => [3,1,1,1,1,2],
        ' ' => [3,1,1,2,1,1],
        '$' => [3,2,1,1,1,1],
        '/' => [1,1,3,1,2,1],
        '+' => [1,1,1,1,4,1],
        '%' => [3,1,2,1,1,1],
    ];

    /**
     * Character-to-value map for check digit computation (values 0–42).
     *
     * @var array<string, int>
     */
    private const CHAR_VALUES = [
        '0' =>  0, '1' =>  1, '2' =>  2, '3' =>  3, '4' =>  4,
        '5' =>  5, '6' =>  6, '7' =>  7, '8' =>  8, '9' =>  9,
        'A' => 10, 'B' => 11, 'C' => 12, 'D' => 13, 'E' => 14,
        'F' => 15, 'G' => 16, 'H' => 17, 'I' => 18, 'J' => 19,
        'K' => 20, 'L' => 21, 'M' => 22, 'N' => 23, 'O' => 24,
        'P' => 25, 'Q' => 26, 'R' => 27, 'S' => 28, 'T' => 29,
        'U' => 30, 'V' => 31, 'W' => 32, 'X' => 33, 'Y' => 34,
        'Z' => 35, '-' => 36, '.' => 37, ' ' => 38, '$' => 39,
        '/' => 40, '+' => 41, '%' => 42,
    ];

    /**
     * Value-to-pattern for all 47 symbols (0–46), needed to render computed check digits.
     * Values 43–46 are the 4 shift symbols used in Full ASCII mode; their patterns are
     * unique across the full symbol table so that a compliant scanner can decode them.
     *
     * @var array<int, int[]>
     */
    private const VALUE_PATTERNS = [
         0 => [1,3,1,1,1,2],  1 => [1,1,1,2,1,3],  2 => [1,1,1,3,1,2],
         3 => [1,1,1,4,1,1],  4 => [1,2,1,1,1,3],  5 => [1,2,1,2,1,2],
         6 => [1,2,1,3,1,1],  7 => [1,1,1,1,1,4],  8 => [1,3,1,2,1,1],
         9 => [1,4,1,1,1,1], 10 => [2,1,1,1,1,3], 11 => [2,1,1,2,1,2],
        12 => [2,1,1,3,1,1], 13 => [2,2,1,1,1,2], 14 => [2,2,1,2,1,1],
        15 => [2,3,1,1,1,1], 16 => [1,1,2,1,1,3], 17 => [1,1,2,2,1,2],
        18 => [1,1,2,3,1,1], 19 => [1,2,2,1,1,2], 20 => [1,3,2,1,1,1],
        21 => [1,1,1,1,2,3], 22 => [1,1,1,2,2,2], 23 => [1,1,1,3,2,1],
        24 => [1,2,1,1,2,2], 25 => [1,3,1,1,2,1], 26 => [2,1,2,1,1,2],
        27 => [2,1,2,2,1,1], 28 => [2,1,1,1,2,2], 29 => [2,1,1,2,2,1],
        30 => [2,2,2,1,1,1], 31 => [2,2,1,1,2,1], 32 => [1,1,2,1,2,2],
        33 => [1,1,2,2,2,1], 34 => [1,2,2,1,2,1], 35 => [1,2,3,1,1,1],
        36 => [1,2,1,1,3,1], 37 => [3,1,1,1,1,2], 38 => [3,1,1,2,1,1],
        39 => [3,2,1,1,1,1], 40 => [1,1,3,1,2,1], 41 => [1,1,1,1,4,1],
        42 => [3,1,2,1,1,1],
        // Shift symbols (values 43–46): unique patterns, sum = 9 each.
        43 => [2,1,1,1,3,1],
        44 => [1,2,1,2,2,1],
        45 => [2,1,2,1,2,1],
        46 => [1,1,3,1,1,2],
    ];

    /** Start/stop symbol: [b,s,b,s,b,s] = 1,1,4,1,1,1 (9 modules). */
    private const START_STOP = [1, 1, 4, 1, 1, 1];

    public function supports(string $type): bool
    {
        return $type === BarcodeType::CODE93;
    }

    /**
     * Returns true only when every character in $value belongs to the basic
     * Code 93 character set (uppercase A–Z, 0–9, space, - . $ / + %).
     * Lowercase letters are not accepted.
     */
    public function validate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        foreach (str_split($value) as $char) {
            if (!isset(self::CHAR_VALUES[$char])) {
                return false;
            }
        }
        return true;
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!$this->validate($value)) {
            throw new \InvalidArgumentException(
                'Code93: value contains characters not supported by this symbology.'
            );
        }

        $height   = (int) ($options['height']   ?? 80);
        $barWidth = (int) ($options['bar_width'] ?? 2);
        $label    = $options['label'] ?? $value;

        $dataValues = array_map(
            fn(string $c): int => self::CHAR_VALUES[$c],
            str_split($value),
        );

        $checkC = $this->computeCheck($dataValues);
        $checkK = $this->computeCheck([...$dataValues, $checkC]);

        // Build flat module-width array:
        // quiet | start(9) | data symbols | C | K | stop(9) | term bar(1) | quiet
        $allWidths = self::START_STOP;
        foreach ($dataValues as $v) {
            foreach (self::VALUE_PATTERNS[$v] as $w) {
                $allWidths[] = $w;
            }
        }
        foreach (self::VALUE_PATTERNS[$checkC] as $w) {
            $allWidths[] = $w;
        }
        foreach (self::VALUE_PATTERNS[$checkK] as $w) {
            $allWidths[] = $w;
        }
        foreach (self::START_STOP as $w) {
            $allWidths[] = $w;
        }
        $allWidths[] = 1; // terminating bar

        $totalWidth = array_sum($allWidths) * $barWidth;
        $bars       = $this->buildBars($allWidths, $barWidth, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }

    /**
     * Compute a single Code 93 check digit.
     *
     * Position weights run 1–20 from right to left, cycling back to 1 after 20
     * (per ISO/IEC 16388).
     *
     * @param int[] $values Numeric values of the characters to include in the sum.
     * @return int           Check digit value (0–46).
     */
    private function computeCheck(array $values): int
    {
        $n   = count($values);
        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $weight = (($n - $i - 1) % 20) + 1;
            $sum   += $weight * $values[$i];
        }
        return $sum % 47;
    }
}
