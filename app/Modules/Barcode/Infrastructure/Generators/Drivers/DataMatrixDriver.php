<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Data Matrix driver (simplified visual stub).
 *
 * Generates a visual SVG representation of Data Matrix with proper
 * finder-pattern borders and data modules derived from the value bytes.
 * This is a structural stub; for production scanning use a dedicated library.
 */
class DataMatrixDriver extends AbstractSvgBarcodeDriver
{
    public function supports(string $type): bool
    {
        return $type === BarcodeType::DATAMATRIX;
    }

    public function validate(string $value): bool
    {
        return $value !== '';
    }

    protected function generateSvg(string $value, array $options): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException('DataMatrix: value must not be empty.');
        }

        $moduleSize = (int) ($options['module_size'] ?? 4);
        $label      = $options['label'] ?? '';

        // Determine a square grid size based on data length.
        $dataLen  = strlen($value);
        $gridSize = max(10, (int) ceil(sqrt($dataLen * 8)) + 2);
        if ($gridSize % 2 !== 0) {
            $gridSize++;
        }

        $bytes   = array_map('ord', str_split($value));
        $modules = $this->buildGrid($gridSize, $bytes);

        $svgSize = $gridSize * $moduleSize;
        $rects   = '';
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                if ($modules[$row][$col]) {
                    $x = $col * $moduleSize;
                    $y = $row * $moduleSize;
                    $rects .= '<rect x="' . $x . '" y="' . $y
                        . '" width="' . $moduleSize . '" height="' . $moduleSize
                        . '" fill="#000000"/>';
                }
            }
        }

        return $this->buildSvg($svgSize, $svgSize, $rects, $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Build a grid with finder borders + pseudo-random data fill. */
    private function buildGrid(int $size, array $bytes): array
    {
        $grid = array_fill(0, $size, array_fill(0, $size, 0));

        // Solid bottom-left L finder (bottom row + left column = all dark)
        for ($i = 0; $i < $size; $i++) {
            $grid[$size - 1][$i] = 1; // bottom row
            $grid[$i][0]         = 1; // left column
        }

        // Alternating top row and right column finder
        for ($i = 0; $i < $size; $i++) {
            $grid[0][$i]         = ($i % 2 === 0) ? 1 : 0; // top row
            $grid[$i][$size - 1] = ($i % 2 === 0) ? 1 : 0; // right column
        }

        // Fill interior with encoded bits
        $bit  = 0;
        $byteIdx = 0;
        for ($row = 1; $row < $size - 1; $row++) {
            for ($col = 1; $col < $size - 1; $col++) {
                if ($byteIdx < count($bytes)) {
                    $grid[$row][$col] = ($bytes[$byteIdx] >> (7 - ($bit % 8))) & 1;
                    $bit++;
                    if ($bit % 8 === 0) {
                        $byteIdx++;
                    }
                }
            }
        }

        return $grid;
    }
}
