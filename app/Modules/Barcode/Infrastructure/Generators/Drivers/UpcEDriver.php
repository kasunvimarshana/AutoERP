<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * UPC-E driver.
 *
 * UPC-E is a zero-suppressed 6-digit barcode.  This driver accepts a
 * 6-digit UPC-E payload (without number-system digit or check digit) or
 * the full 8-digit form (1 + 6 + 1).
 *
 * Structure: guard(3+1) | 6 × E-pattern(7) | guard(6)
 *
 * The E-pattern for each digit position uses one of two 4-element
 * bar/space patterns selected by a parity table derived from the
 * implied UPC-A check digit.
 */
class UpcEDriver extends AbstractSvgBarcodeDriver
{
    /**
     * Even-parity (E-set / G-codes) 7-module patterns for each digit.
     * These are the EAN-13 G-codes: [space1, bar1, space2, bar2] in module units.
     * They start with a space (light), matching the odd-index position after the left guard.
     */
    private const E_CODES = [
        [1,1,2,3], [1,2,2,2], [2,2,1,2], [1,1,4,1], [2,3,1,1],
        [1,3,2,1], [4,1,1,1], [2,1,3,1], [3,1,2,1], [2,1,1,3],
    ];

    /**
     * Odd-parity (O-set / L-codes) 7-module patterns for each digit.
     * Identical to EAN-13 L-codes: [space1, bar1, space2, bar2].
     */
    private const O_CODES = [
        [3,2,1,1], [2,2,2,1], [2,1,2,2], [1,4,1,1], [1,1,3,2],
        [1,2,3,1], [1,1,1,4], [1,3,1,2], [1,2,1,3], [3,1,1,2],
    ];

    /**
     * Parity table for number-system digit 0, indexed by check digit.
     * 0 = odd parity (O-code / L-code), 1 = even parity (E-code / G-code).
     * Standard UPC-E parity for NS=0, check digits 0–9.
     * @var array<int, int[]>
     */
    private const PARITY = [
        0 => [0,0,0,1,1,1],
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
        return $type === BarcodeType::UPCE;
    }

    public function validate(string $value): bool
    {
        $len = strlen($value);
        return ($len === 6 || $len === 8) && ctype_digit($value);
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!$this->validate($value)) {
            throw new \InvalidArgumentException('UPC-E: expected 6 or 8 digits.');
        }

        // Normalise to the 6-digit payload + derive check digit.
        if (strlen($value) === 8) {
            $payload = substr($value, 1, 6);
            $check   = (int) $value[7];
        } else {
            $payload = $value;
            $check   = $this->computeCheckDigit($payload);
        }

        $digits = array_map('intval', str_split($payload));
        $parity = self::PARITY[$check];

        $height = (int) ($options['height']    ?? 70);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        $widths = [];

        // Left guard: bar-space-bar  [1,1,1] (3 modules = 101)
        array_push($widths, 1, 1, 1);

        // 6 data characters
        for ($i = 0; $i < 6; $i++) {
            $d   = $digits[$i];
            $pat = ($parity[$i] === 1) ? self::E_CODES[$d] : self::O_CODES[$d];
            foreach ($pat as $w) {
                $widths[] = $w;
            }
        }

        // Right guard: space-bar-space-bar-space-bar  [1,1,1,1,1,1]
        array_push($widths, 1, 1, 1, 1, 1, 1);

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function computeCheckDigit(string $sixDigits): int
    {
        // Expand UPC-E to UPC-A to derive the check digit.
        $d = str_split($sixDigits);
        $last = (int) $d[5];

        if ($last <= 2) {
            $upca = '0' . $d[0] . $d[1] . $last . '0000' . $d[2] . $d[3] . $d[4];
        } elseif ($last === 3) {
            $upca = '0' . $d[0] . $d[1] . $d[2] . '00000' . $d[3] . $d[4];
        } elseif ($last === 4) {
            $upca = '0' . $d[0] . $d[1] . $d[2] . $d[3] . '00000' . $d[4];
        } else {
            $upca = '0' . $d[0] . $d[1] . $d[2] . $d[3] . $d[4] . '0000' . $last;
        }

        $upca = str_pad($upca, 11, '0', STR_PAD_RIGHT);
        $sum  = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int) $upca[$i] * (($i % 2 === 0) ? 3 : 1);
        }
        return (10 - ($sum % 10)) % 10;
    }
}
