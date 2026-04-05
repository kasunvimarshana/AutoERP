<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * ITF-14 driver (Interleaved 2-of-5, 14 digits).
 *
 * Pairs of digits are interleaved: the first digit of each pair is encoded
 * in the bars, the second in the spaces.
 * Narrow element = 1 unit, wide element = 3 units.
 *
 * Structure: start(4) | interleaved pairs | stop(5)
 */
class Itf14Driver extends AbstractSvgBarcodeDriver
{
    /**
     * Narrow/wide pattern per digit (5 elements: bar or space widths).
     * 0 = narrow, 1 = wide.
     * @var array<int, int[]>
     */
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
        return $type === BarcodeType::ITF14;
    }

    public function validate(string $value): bool
    {
        return strlen($value) === 14 && ctype_digit($value);
    }

    protected function generateSvg(string $value, array $options): string
    {
        if (!ctype_digit($value) || strlen($value) !== 14) {
            throw new \InvalidArgumentException('ITF-14: expected exactly 14 digits.');
        }

        $height = (int) ($options['height']    ?? 80);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        $widths = [];

        // Start: narrow bar, narrow space, narrow bar, narrow space
        array_push($widths, 1, 1, 1, 1);

        // Encode digit pairs
        $digits = array_map('intval', str_split($value));
        for ($i = 0; $i < 14; $i += 2) {
            $bars   = self::DIGIT_PATTERN[$digits[$i]];
            $spaces = self::DIGIT_PATTERN[$digits[$i + 1]];

            // Interleave: 5 bar widths interleaved with 5 space widths
            for ($j = 0; $j < 5; $j++) {
                $widths[] = $bars[$j]   ? self::WIDE : self::NARROW; // bar
                $widths[] = $spaces[$j] ? self::WIDE : self::NARROW; // space
            }
        }

        // Stop: wide bar, narrow space, narrow bar
        array_push($widths, 3, 1, 1);

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }
}
