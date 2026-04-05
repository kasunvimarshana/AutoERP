<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * UPC-A driver.
 *
 * Encodes 11 or 12 decimal digits.  UPC-A is structurally identical to
 * EAN-13 with an implied leading zero; the same L/R encoding tables are used.
 *
 * Structure: guard(3) | L-set(6×7) | centre(5) | R-set(6×7) | guard(3)
 */
class UpcADriver extends AbstractSvgBarcodeDriver
{
    private const L_CODES = [
        [3,2,1,1], [2,2,2,1], [2,1,2,2], [1,4,1,1], [1,1,3,2],
        [1,2,3,1], [1,1,1,4], [1,3,1,2], [1,2,1,3], [3,1,1,2],
    ];

    private const R_CODES = [
        [3,2,1,1], [2,2,2,1], [2,1,2,2], [1,4,1,1], [1,1,3,2],
        [1,2,3,1], [1,1,1,4], [1,3,1,2], [1,2,1,3], [3,1,1,2],
    ];

    public function supports(string $type): bool
    {
        return $type === BarcodeType::UPCA;
    }

    public function validate(string $value): bool
    {
        $len = strlen($value);
        if (($len !== 11 && $len !== 12) || !ctype_digit($value)) {
            return false;
        }
        if ($len === 12) {
            return $this->checkDigit(substr($value, 0, 11)) === (int) $value[11];
        }
        return true;
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!ctype_digit($value) || strlen($value) < 11 || strlen($value) > 12) {
            throw new \InvalidArgumentException('UPC-A: expected 11 or 12 digits.');
        }

        if (strlen($value) === 11) {
            $value .= (string) $this->checkDigit($value);
        } else {
            $expected = $this->checkDigit(substr($value, 0, 11));
            if ((int) $value[11] !== $expected) {
                throw new \InvalidArgumentException(
                    "UPC-A: invalid check digit (expected {$expected})."
                );
            }
        }

        $digits = array_map('intval', str_split($value));
        $height = (int) ($options['height']    ?? 80);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        $widths = [];

        // Left guard [1,1,1]
        array_push($widths, 1, 1, 1);

        // Left 6 digits – L-codes
        for ($i = 0; $i < 6; $i++) {
            foreach (self::L_CODES[$digits[$i]] as $w) {
                $widths[] = $w;
            }
        }

        // Centre guard [1,1,1,1,1]
        array_push($widths, 1, 1, 1, 1, 1);

        // Right 6 digits – R-codes
        for ($i = 6; $i < 12; $i++) {
            foreach (self::R_CODES[$digits[$i]] as $w) {
                $widths[] = $w;
            }
        }

        // Right guard [1,1,1]
        array_push($widths, 1, 1, 1);

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function checkDigit(string $elevenDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int) $elevenDigits[$i] * (($i % 2 === 0) ? 3 : 1);
        }
        return (10 - ($sum % 10)) % 10;
    }
}
