<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * EAN-8 driver.
 *
 * Encodes 7 or 8 decimal digits.  Check digit is computed or verified
 * automatically.
 *
 * Structure: guard(3) | L-set(4×7) | centre(5) | R-set(4×7) | guard(3)
 */
class Ean8Driver extends AbstractSvgBarcodeDriver
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
        return $type === BarcodeType::EAN8;
    }

    public function validate(string $value): bool
    {
        $len = strlen($value);
        if (($len !== 7 && $len !== 8) || !ctype_digit($value)) {
            return false;
        }
        if ($len === 8) {
            return $this->checkDigit(substr($value, 0, 7)) === (int) $value[7];
        }
        return true;
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!ctype_digit($value) || strlen($value) < 7 || strlen($value) > 8) {
            throw new \InvalidArgumentException('EAN-8: expected 7 or 8 digits.');
        }

        if (strlen($value) === 7) {
            $value .= (string) $this->checkDigit($value);
        } else {
            $expected = $this->checkDigit(substr($value, 0, 7));
            if ((int) $value[7] !== $expected) {
                throw new \InvalidArgumentException(
                    "EAN-8: invalid check digit (expected {$expected})."
                );
            }
        }

        $digits = array_map('intval', str_split($value));
        $height = (int) ($options['height']    ?? 70);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        $widths = [];

        // Left guard [1,1,1]
        array_push($widths, 1, 1, 1);

        // Left 4 digits – L-codes
        for ($i = 0; $i < 4; $i++) {
            foreach (self::L_CODES[$digits[$i]] as $w) {
                $widths[] = $w;
            }
        }

        // Centre guard [1,1,1,1,1]
        array_push($widths, 1, 1, 1, 1, 1);

        // Right 4 digits – R-codes
        for ($i = 4; $i < 8; $i++) {
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

    private function checkDigit(string $sevenDigits): int
    {
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $sevenDigits[$i] * (($i % 2 === 0) ? 3 : 1);
        }
        return (10 - ($sum % 10)) % 10;
    }
}
