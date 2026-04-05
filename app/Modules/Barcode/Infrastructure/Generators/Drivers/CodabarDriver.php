<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Codabar driver.
 *
 * Encodes digits 0–9 and special characters - $ : / . +
 * Start/stop characters are A, B, C, or D (case-insensitive).
 *
 * Each symbol is 7 elements: 4 bars and 3 spaces (alternating, starting
 * with a bar).  Narrow = 1 unit, wide = 3 units.
 * Inter-character gap = 1 narrow space.
 */
class CodabarDriver extends AbstractSvgBarcodeDriver
{
    /**
     * Codabar encoding table.
     * 7-element arrays: bar, space, bar, space, bar, space, bar.
     * 0 = narrow, 1 = wide.
     *
     * @var array<string, int[]>
     */
    private const PATTERNS = [
        '0' => [0,0,0,0,0,1,1],
        '1' => [0,0,0,0,1,1,0],
        '2' => [0,0,0,1,0,0,1],
        '3' => [1,1,0,0,0,0,0],
        '4' => [0,0,1,0,0,1,0],
        '5' => [1,0,0,0,0,1,0],
        '6' => [0,1,0,0,0,0,1],
        '7' => [0,1,0,0,1,0,0],
        '8' => [0,1,0,1,0,0,0],
        '9' => [1,0,0,1,0,0,0],
        '-' => [0,0,0,1,1,0,0],
        '$' => [0,0,1,1,0,0,0],
        ':' => [1,0,0,0,1,0,1],
        '/' => [1,0,1,0,0,0,1],
        '.' => [1,0,1,0,1,0,0],
        '+' => [0,0,1,0,1,0,1],
        'A' => [0,0,1,1,0,1,0],
        'B' => [0,1,0,1,0,0,1],  // also used as stop
        'C' => [0,0,0,1,0,1,1],
        'D' => [0,0,0,1,1,1,0],
    ];

    private const NARROW = 1;
    private const WIDE   = 3;
    private const GAP    = 1;

    /** Valid data characters (excluding start/stop). */
    private const DATA_CHARS = '0123456789-$:/.+';

    public function supports(string $type): bool
    {
        return $type === BarcodeType::CODABAR;
    }

    public function validate(string $value): bool
    {
        if (strlen($value) < 3) {
            return false;
        }

        $upper = strtoupper($value);
        $start = $upper[0];
        $stop  = $upper[strlen($upper) - 1];

        if (!in_array($start, ['A','B','C','D'], true) || !in_array($stop, ['A','B','C','D'], true)) {
            return false;
        }

        $data = substr($upper, 1, -1);
        for ($i = 0; $i < strlen($data); $i++) {
            if (strpos(self::DATA_CHARS, $data[$i]) === false) {
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
                'Codabar: value must begin and end with A/B/C/D and contain valid data characters.'
            );
        }

        $height = (int) ($options['height']    ?? 80);
        $bw     = (int) ($options['bar_width']  ?? 2);
        $label  = $options['label'] ?? $value;

        $chars   = str_split($upper);
        $widths  = [];

        foreach ($chars as $idx => $char) {
            $pattern = self::PATTERNS[$char];
            foreach ($pattern as $wide) {
                $widths[] = $wide ? self::WIDE : self::NARROW;
            }
            // Inter-character gap between all symbols except the last.
            if ($idx < count($chars) - 1) {
                $widths[] = self::GAP;
            }
        }

        $totalWidth = array_sum($widths) * $bw;
        $bars       = $this->buildBars($widths, $bw, $height);

        return $this->buildSvg($totalWidth, $height, $bars, (string) $label, $options);
    }
}
