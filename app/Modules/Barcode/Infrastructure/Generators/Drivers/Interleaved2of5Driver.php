<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Interleaved 2-of-5 (general-purpose) driver.
 *
 * Identical encoding to ITF-14 but accepts any even-length numeric string.
 * An odd-length payload is zero-padded on the left.
 */
class Interleaved2of5Driver extends AbstractSvgBarcodeDriver
{
    /** @var array<int, int[]> */
    private const DIGIT_PATTERN = [
        0 => [0,0,1,1,0],
        1 => [1,0,0,0,1],
        2 => [0,1,0,0,1],
        3 => [1,1,0,0,0],
        4 => [0,0,1,0,1],
        5 => [1,0,1,0,0],
        6 => [0,1,1,0,0],
        7 => [0,0,0,1,1],
        8 => [1,0,0,1,0],
        9 => [0,1,0,1,0],
    ];

    private const NARROW = 1;
    private const WIDE   = 3;

    public function supports(string $type): bool
    {
        return $type === BarcodeType::INTERLEAVED2OF5;
    }

    public function validate(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!$this->validate($value)) {
            throw new \InvalidArgumentException(
                'Interleaved 2-of-5: value must be a non-empty numeric string.'
            );
        }

        // Ensure even length
        if (strlen($value) % 2 !== 0) {
            $value = '0' . $value;
        }

        $height = (int) ($options['height']    ?? 80);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        $widths = [];

        // Start: narrow-bar, narrow-space, narrow-bar, narrow-space
        array_push($widths, 1, 1, 1, 1);

        $digits = array_map('intval', str_split($value));
        for ($i = 0, $len = count($digits); $i < $len; $i += 2) {
            $bars   = self::DIGIT_PATTERN[$digits[$i]];
            $spaces = self::DIGIT_PATTERN[$digits[$i + 1]];

            for ($j = 0; $j < 5; $j++) {
                $widths[] = $bars[$j]   ? self::WIDE : self::NARROW;
                $widths[] = $spaces[$j] ? self::WIDE : self::NARROW;
            }
        }

        // Stop: wide-bar, narrow-space, narrow-bar
        array_push($widths, 3, 1, 1);

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }
}
