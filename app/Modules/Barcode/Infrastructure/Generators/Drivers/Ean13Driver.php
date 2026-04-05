<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * EAN-13 driver.
 *
 * Encodes 12 or 13 decimal digits.  When 12 digits are supplied the check
 * digit is appended automatically.  When 13 digits are supplied the check
 * digit is verified.
 *
 * Structure: [quiet] guard(3) | L/G-set(6×7) | centre(5) | R-set(6×7) | guard(3) [quiet]
 */
class Ean13Driver extends AbstractSvgBarcodeDriver
{
    /** L-code patterns (left, odd parity). */
    private const L_CODES = [
        [3,2,1,1], [2,2,2,1], [2,1,2,2], [1,4,1,1], [1,1,3,2],
        [1,2,3,1], [1,1,1,4], [1,3,1,2], [1,2,1,3], [3,1,1,2],
    ];

    /** G-code patterns (left, even parity – mirror of R). */
    private const G_CODES = [
        [1,1,2,3], [1,2,2,2], [2,2,1,2], [1,1,4,1], [2,3,1,1],
        [1,3,2,1], [4,1,1,1], [2,1,3,1], [3,1,2,1], [2,1,1,3],
    ];

    /** R-code patterns (right side, always odd-bar-start). */
    private const R_CODES = [
        [3,2,1,1], [2,2,2,1], [2,1,2,2], [1,4,1,1], [1,1,3,2],
        [1,2,3,1], [1,1,1,4], [1,3,1,2], [1,2,1,3], [3,1,1,2],
    ];

    /**
     * First-digit parity table: 0 = L-code, 1 = G-code for positions 1–6.
     * @var array<int, int[]>
     */
    private const PARITY = [
        0 => [0,0,0,0,0,0],
        1 => [0,0,1,0,1,1],
        2 => [0,0,1,1,0,1],
        3 => [0,0,1,1,1,0],
        4 => [0,1,0,0,1,1],
        5 => [0,1,1,0,0,1],
        6 => [0,1,1,1,0,0],
        7 => [0,1,0,1,0,1],
        8 => [0,1,0,1,1,0],
        9 => [0,1,1,0,1,0],
    ];

    public function supports(string $type): bool
    {
        return $type === BarcodeType::EAN13;
    }

    public function validate(string $value): bool
    {
        $len = strlen($value);
        if (($len !== 12 && $len !== 13) || !ctype_digit($value)) {
            return false;
        }
        if ($len === 13) {
            return $this->checkDigit(substr($value, 0, 12)) === (int) $value[12];
        }
        return true;
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!ctype_digit($value) || strlen($value) < 12 || strlen($value) > 13) {
            throw new \InvalidArgumentException('EAN-13: expected 12 or 13 digits.');
        }

        if (strlen($value) === 12) {
            $value .= (string) $this->checkDigit($value);
        } else {
            $expected = $this->checkDigit(substr($value, 0, 12));
            if ((int) $value[12] !== $expected) {
                throw new \InvalidArgumentException(
                    "EAN-13: invalid check digit (expected {$expected})."
                );
            }
        }

        $digits  = array_map('intval', str_split($value));
        $height  = (int) ($options['height']    ?? 80);
        $bw      = (int) ($options['bar_width']  ?? 2);
        $label   = $options['label'] ?? $value;

        // Guard bars: normal [1,1,1], centre [1,1,1,1,1], end [1,1,1]
        // Each digit encodes to 4 widths (bar+space+bar+space = 7 modules).
        $widths = [];

        // Left quiet zone handled by buildSvg's quietZone.
        // Normal guard [1,1,1] = bar-space-bar
        array_push($widths, 1, 1, 1);

        // Left 6 digits (positions 1–6), parity determined by first digit.
        $parity = self::PARITY[$digits[0]];
        for ($i = 0; $i < 6; $i++) {
            $d = $digits[$i + 1];
            $pat = ($parity[$i] === 0) ? self::L_CODES[$d] : self::G_CODES[$d];
            foreach ($pat as $w) {
                $widths[] = $w;
            }
        }

        // Centre guard [1,1,1,1,1] = space-bar-space-bar-space
        array_push($widths, 1, 1, 1, 1, 1);

        // Right 6 digits (positions 7–12), always R-codes.
        for ($i = 0; $i < 6; $i++) {
            $d = $digits[$i + 7];
            foreach (self::R_CODES[$d] as $w) {
                $widths[] = $w;
            }
        }

        // End guard [1,1,1]
        array_push($widths, 1, 1, 1);

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function checkDigit(string $twelveDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $twelveDigits[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        return (10 - ($sum % 10)) % 10;
    }
}
