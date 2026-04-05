<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Code 128 – Code Set B driver.
 *
 * Encodes all ASCII characters 32–127 (space through DEL).
 * Implements the full standard encoding table, correct check-digit
 * calculation, and alternating-bar SVG rendering.
 */
class Code128Driver extends AbstractSvgBarcodeDriver
{
    /**
     * Standard Code 128 bar/space pattern table.
     * Index 0–102 = data symbols, 103 = Start A, 104 = Start B, 105 = Stop.
     * Each entry is an array of widths [b1,s1,b2,s2,b3,s3] (6 elements)
     * or [b1,s1,b2,s2,b3,s3,b4] (7 elements for Stop only).
     *
     * @var array<int, int[]>
     */
    private const PATTERNS = [
        /* 0  */ [2,1,2,2,2,2],
        /* 1  */ [2,2,2,1,2,2],
        /* 2  */ [2,2,2,2,2,1],
        /* 3  */ [1,2,1,2,2,3],
        /* 4  */ [1,2,1,3,2,2],
        /* 5  */ [1,3,1,2,2,2],
        /* 6  */ [1,2,2,2,1,3],
        /* 7  */ [1,2,2,3,1,2],
        /* 8  */ [1,3,2,2,1,2],
        /* 9  */ [2,2,1,2,1,3],
        /* 10 */ [2,2,1,3,1,2],
        /* 11 */ [2,3,1,2,1,2],
        /* 12 */ [1,1,2,2,3,2],
        /* 13 */ [1,2,2,1,3,2],
        /* 14 */ [1,2,2,2,3,1],
        /* 15 */ [1,1,3,2,2,2],
        /* 16 */ [1,2,3,1,2,2],
        /* 17 */ [1,2,3,2,2,1],
        /* 18 */ [2,2,3,2,1,1],
        /* 19 */ [2,2,1,1,3,2],
        /* 20 */ [2,2,1,2,3,1],
        /* 21 */ [2,1,3,2,1,2],
        /* 22 */ [2,2,3,1,1,2],
        /* 23 */ [3,1,2,1,3,1],
        /* 24 */ [3,1,1,2,2,2],
        /* 25 */ [3,2,1,1,2,2],
        /* 26 */ [3,2,1,2,2,1],
        /* 27 */ [3,1,2,2,1,2],
        /* 28 */ [3,2,2,1,1,2],
        /* 29 */ [3,2,2,2,1,1],
        /* 30 */ [2,1,2,1,2,3],
        /* 31 */ [2,1,2,3,2,1],
        /* 32 */ [2,3,2,1,2,1],
        /* 33 */ [1,1,1,3,2,3],
        /* 34 */ [1,3,1,1,3,2],
        /* 35 */ [1,3,1,3,1,2],
        /* 36 */ [1,1,2,3,1,3],
        /* 37 */ [1,3,2,1,1,3],
        /* 38 */ [2,1,1,3,1,3],
        /* 39 */ [1,2,1,1,2,4],
        /* 40 */ [1,4,1,1,2,2],
        /* 41 */ [2,2,1,4,1,1],
        /* 42 */ [3,3,1,1,1,2],
        /* 43 */ [2,1,1,4,1,2],
        /* 44 */ [1,1,2,4,1,2],
        /* 45 */ [2,1,2,2,1,4],
        /* 46 */ [2,1,2,4,1,2],
        /* 47 */ [2,4,2,1,1,1],
        /* 48 */ [1,1,1,1,4,3],
        /* 49 */ [1,1,1,3,4,1],
        /* 50 */ [1,3,1,1,4,1],
        /* 51 */ [1,1,4,1,1,3],
        /* 52 */ [1,1,4,3,1,1],
        /* 53 */ [4,1,1,1,1,3],
        /* 54 */ [4,1,1,3,1,1],
        /* 55 */ [1,2,4,1,1,2],
        /* 56 */ [1,2,4,2,1,1],
        /* 57 */ [4,1,1,2,1,2],
        /* 58 */ [4,2,1,1,1,2],
        /* 59 */ [4,2,1,2,1,1],
        /* 60 */ [2,1,4,1,2,1],
        /* 61 */ [2,1,1,2,1,4],
        /* 62 */ [2,1,1,4,2,1],
        /* 63 */ [4,1,2,1,1,2],
        /* 64 */ [1,1,2,1,2,4],
        /* 65 */ [1,1,2,4,2,1],
        /* 66 */ [1,2,2,1,2,3],
        /* 67 */ [1,2,2,3,2,1],
        /* 68 */ [1,2,4,1,2,1],
        /* 69 */ [1,4,1,2,1,2],
        /* 70 */ [1,4,2,1,1,2],
        /* 71 */ [1,4,2,2,1,1],
        /* 72 */ [4,1,2,2,1,1],
        /* 73 */ [4,2,2,1,1,1],
        /* 74 */ [2,2,4,2,1,1],
        /* 75 */ [2,4,2,2,1,1],
        /* 76 */ [4,4,1,1,1,1],
        /* 77 */ [1,1,2,2,4,2],
        /* 78 */ [1,2,2,1,4,2],
        /* 79 */ [1,2,2,2,4,1],
        /* 80 */ [1,1,4,2,2,2],
        /* 81 */ [1,2,4,1,2,2],
        /* 82 */ [2,1,2,1,4,2],
        /* 83 */ [2,1,4,1,2,2],
        /* 84 */ [4,1,2,1,2,2],
        /* 85 */ [3,1,1,1,4,2],
        /* 86 */ [2,1,1,2,4,2],
        /* 87 */ [1,1,2,1,4,3],
        /* 88 */ [1,2,1,4,1,2],
        /* 89 */ [1,4,1,2,2,1],
        /* 90 */ [3,1,1,4,1,2],
        /* 91 */ [1,1,1,2,3,3],
        /* 92 */ [1,1,1,4,3,1],
        /* 93 */ [1,3,1,4,1,1],
        /* 94 */ [1,1,4,1,3,1],
        /* 95 */ [4,1,1,1,3,1],
        /* 96 */ [2,2,1,1,1,4],
        /* 97 */ [4,3,1,1,1,1],
        /* 98 */ [1,1,3,1,4,2],
        /* 99 */ [1,1,4,1,2,3],
        /* 100 */ [1,1,2,3,4,1],
        /* 101 */ [3,1,3,1,1,2],
        /* 102 */ [3,1,1,2,3,1],
        /* 103 = Start A */ [2,1,1,4,1,2],
        /* 104 = Start B */ [2,1,1,2,1,4],
        /* 105 = Stop    */ [2,3,3,1,1,1,2],
    ];

    private const START_B = 104;
    private const STOP    = 105;

    public function supports(string $type): bool
    {
        return $type === BarcodeType::CODE128;
    }

    public function validate(string $value): bool
    {
        foreach (str_split($value) as $char) {
            $ord = ord($char);
            if ($ord < 32 || $ord > 127) {
                return false;
            }
        }
        return $value !== '';
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!$this->validate($value)) {
            throw new \InvalidArgumentException(
                'Code128: value contains characters outside ASCII 32–127.'
            );
        }

        $height   = (int) ($options['height']    ?? 80);
        $barWidth = (int) ($options['bar_width']  ?? 2);
        $label    = $options['label'] ?? $value;

        // ── Build symbol sequence ─────────────────────────────────────────────
        // Convert each character to its Code-Set-B code value (ASCII - 32).
        $codeValues = [];
        foreach (str_split($value) as $char) {
            $codeValues[] = ord($char) - 32;
        }

        // Check digit: start value + weighted sum of data values, mod 103.
        $checksum = self::START_B;
        foreach ($codeValues as $i => $cv) {
            $checksum += ($i + 1) * $cv;
        }
        $checksum %= 103;

        // Symbol order: Start B | data symbols | check digit | Stop
        $symbols = array_merge([self::START_B], $codeValues, [$checksum, self::STOP]);

        // ── Render bars ───────────────────────────────────────────────────────
        $allWidths = [];
        foreach ($symbols as $sym) {
            foreach (self::PATTERNS[$sym] as $w) {
                $allWidths[] = $w;
            }
        }

        $totalUnits = array_sum($allWidths);
        $totalWidth = $totalUnits * $barWidth;

        $bars = $this->buildBars($allWidths, $barWidth, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }
}
