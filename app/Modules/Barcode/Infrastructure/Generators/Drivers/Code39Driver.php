<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Code 39 driver.
 *
 * Encodes uppercase A–Z, digits 0–9, space, and the special characters
 * - . $ / + % *.  The asterisk (*) is used exclusively as start/stop
 * and must not appear in the data payload.
 *
 * Each symbol is represented by 5 bars and 4 spaces (9 elements total),
 * where 0 = narrow (1 unit) and 1 = wide (3 units).
 * Inter-character gap = 1 narrow space.
 */
class Code39Driver extends AbstractSvgBarcodeDriver
{
    /**
     * Code 39 encoding table.
     * Each entry: 9-element array; indices 0,2,4,6,8 = bars, 1,3,5,7 = spaces.
     * 0 = narrow (1 unit), 1 = wide (3 units).
     *
     * @var array<string, int[]>
     */
    private const PATTERNS = [
        '0' => [0,0,0,1,1,0,1,0,0],
        '1' => [1,0,0,1,0,0,0,0,1],
        '2' => [0,0,1,1,0,0,0,0,1],
        '3' => [1,0,1,1,0,0,0,0,0],
        '4' => [0,0,0,1,1,0,0,0,1],
        '5' => [1,0,0,1,1,0,0,0,0],
        '6' => [0,0,1,1,1,0,0,0,0],
        '7' => [0,0,0,1,0,0,1,0,1],
        '8' => [1,0,0,1,0,0,1,0,0],
        '9' => [0,0,1,1,0,0,1,0,0],
        'A' => [1,0,0,0,0,1,0,0,1],
        'B' => [0,0,1,0,0,1,0,0,1],
        'C' => [1,0,1,0,0,1,0,0,0],
        'D' => [0,0,0,0,1,1,0,0,1],
        'E' => [1,0,0,0,1,1,0,0,0],
        'F' => [0,0,1,0,1,1,0,0,0],
        'G' => [0,0,0,0,0,1,1,0,1],
        'H' => [1,0,0,0,0,1,1,0,0],
        'I' => [0,0,1,0,0,1,1,0,0],
        'J' => [0,0,0,0,1,1,1,0,0],
        'K' => [1,0,0,0,0,0,0,1,1],
        'L' => [0,0,1,0,0,0,0,1,1],
        'M' => [1,0,1,0,0,0,0,1,0],
        'N' => [0,0,0,0,1,0,0,1,1],
        'O' => [1,0,0,0,1,0,0,1,0],
        'P' => [0,0,1,0,1,0,0,1,0],
        'Q' => [0,0,0,0,0,0,1,1,1],
        'R' => [1,0,0,0,0,0,1,1,0],
        'S' => [0,0,1,0,0,0,1,1,0],
        'T' => [0,0,0,0,1,0,1,1,0],
        'U' => [1,1,0,0,0,0,0,0,1],
        'V' => [0,1,1,0,0,0,0,0,1],
        'W' => [1,1,1,0,0,0,0,0,0],
        'X' => [0,1,0,0,1,0,0,0,1],
        'Y' => [1,1,0,0,1,0,0,0,0],
        'Z' => [0,1,1,0,1,0,0,0,0],
        '-' => [0,1,0,0,0,0,1,0,1],
        '.' => [1,1,0,0,0,0,1,0,0],
        ' ' => [0,1,1,0,0,0,1,0,0],
        '$' => [0,1,0,1,0,1,0,0,0],
        '/' => [0,1,0,1,0,0,0,1,0],
        '+' => [0,1,0,0,0,1,0,1,0],
        '%' => [0,0,0,1,0,1,0,1,0],
        '*' => [0,1,0,0,1,0,1,0,0], // start / stop
    ];

    private const NARROW = 1;
    private const WIDE   = 3;
    private const GAP    = 1; // inter-character space width

    public function supports(string $type): bool
    {
        return $type === BarcodeType::CODE39;
    }

    public function validate(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $upper = strtoupper($value);
        foreach (str_split($upper) as $char) {
            if (!isset(self::PATTERNS[$char]) || $char === '*') {
                return false;
            }
        }
        return true;
    }

    protected function generateSvg(string $value, array $options): string
    {
        $upper = strtoupper($value);
        if (!$this->validate($upper)) {
            throw new \InvalidArgumentException(
                'Code39: value contains characters not supported by this symbology.'
            );
        }

        $height   = (int) ($options['height']    ?? 80);
        $barWidth = (int) ($options['bar_width']  ?? 2);
        $label    = $options['label'] ?? $value;

        // Characters to encode, wrapped with start/stop asterisks.
        $chars = array_merge(['*'], str_split($upper), ['*']);

        // Build flat width array.
        $allWidths = [];
        foreach ($chars as $idx => $char) {
            $pattern = self::PATTERNS[$char];
            foreach ($pattern as $pos => $wide) {
                $allWidths[] = $wide ? self::WIDE : self::NARROW;
            }
            // Add inter-character gap (narrow space) between symbols, not after last.
            if ($idx < count($chars) - 1) {
                $allWidths[] = self::GAP; // space element (not a bar)
            }
        }

        $totalWidth = array_sum($allWidths) * $barWidth;
        $bars       = $this->buildBars($allWidths, $barWidth, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }
}
