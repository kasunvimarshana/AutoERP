<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * MSI (Modified Plessey) driver.
 *
 * Encodes digits 0–9 used for inventory bin labels.
 * Each digit is encoded as 8 alternating bar/space widths from its 4-bit
 * binary representation: 0-bit → narrow (1u bar + 2u space), 1-bit → wide
 * (3u bar + 1u space).
 *
 * Structure: start(1 wide bar) | data | check digit (Luhn) | stop(1 wide + 1 narrow bars)
 */
class MsiDriver extends AbstractSvgBarcodeDriver
{
    public function supports(string $type): bool
    {
        return $type === BarcodeType::MSI;
    }

    public function validate(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!$this->validate($value)) {
            throw new \InvalidArgumentException('MSI: value must be a non-empty numeric string.');
        }

        $height = (int) ($options['height']    ?? 80);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        // Append Luhn check digit.
        $payload = $value . $this->luhnCheckDigit($value);

        $widths = [];

        // Start: one wide bar (3u)
        $widths[] = 3;

        // Each digit encodes as 4 bits (MSB first); each bit is bar+space pair.
        foreach (str_split($payload) as $char) {
            $nibble = (int) $char;
            for ($bit = 3; $bit >= 0; $bit--) {
                if (($nibble >> $bit) & 1) {
                    // Wide bar (3) + narrow space (1)
                    $widths[] = 3; // bar
                    $widths[] = 1; // space
                } else {
                    // Narrow bar (1) + wide space (2)
                    $widths[] = 1; // bar
                    $widths[] = 2; // space
                }
            }
        }

        // Stop: wide bar (3), narrow space (1), narrow bar (1)
        array_push($widths, 3, 1, 1);

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function luhnCheckDigit(string $digits): int
    {
        // Double every other digit from the right (starting at the second-to-last).
        $sum  = 0;
        $alt  = true;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $d = (int) $digits[$i];
            if ($alt) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $alt  = !$alt;
        }
        return (10 - ($sum % 10)) % 10;
    }
}
