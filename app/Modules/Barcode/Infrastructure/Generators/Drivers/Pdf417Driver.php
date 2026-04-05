<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * PDF417 driver (simplified visual stub).
 *
 * Renders a stacked linear barcode appearance.  Each row contains
 * a left/right row indicator and a codeword encoded as 17 modules.
 * This is a structural stub; for production use a dedicated library.
 */
class Pdf417Driver extends AbstractSvgBarcodeDriver
{
    /** Simplified codeword patterns: 17-module (bar+space) sequences. */
    private const CODEWORD_WIDTH  = 17;
    private const ROW_HEIGHT      = 4; // modules per row height

    public function supports(string $type): bool
    {
        return $type === BarcodeType::PDF417;
    }

    public function validate(string $value): bool
    {
        return $value !== '';
    }

    protected function generateSvg(string $value, array $options): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException('PDF417: value must not be empty.');
        }

        $moduleSize = (int) ($options['module_size'] ?? 2);
        $cols       = (int) ($options['columns']     ?? 4); // data columns
        $label      = $options['label'] ?? '';

        // Convert value to byte codewords (simplified – each byte becomes one codeword).
        $bytes      = array_map('ord', str_split($value));
        // Pad to a multiple of $cols
        while (count($bytes) % $cols !== 0) {
            $bytes[] = 0;
        }
        $rows = intdiv(count($bytes), $cols);

        $svgCols    = $cols + 2;              // +2 for row indicators
        $moduleW    = $moduleSize;
        $moduleH    = $moduleSize * self::ROW_HEIGHT;
        $rowWidth   = $svgCols * self::CODEWORD_WIDTH * $moduleW;
        $svgHeight  = $rows * $moduleH;

        $rects = '';
        for ($row = 0; $row < $rows; $row++) {
            $y = $row * $moduleH;

            // Left row indicator: simple solid bar of 1 module width
            $rects .= '<rect x="0" y="' . $y . '" width="' . $moduleW
                . '" height="' . $moduleH . '" fill="#000000"/>';

            // Data columns
            for ($col = 0; $col < $cols; $col++) {
                $byteVal  = $bytes[$row * $cols + $col];
                $x        = (1 + $col) * self::CODEWORD_WIDTH * $moduleW;
                $rects   .= $this->renderCw($byteVal, $x, $y, $moduleW, $moduleH);
            }

            // Right row indicator
            $xRight = ($svgCols - 1) * self::CODEWORD_WIDTH * $moduleW;
            $rects .= '<rect x="' . $xRight . '" y="' . $y . '" width="' . $moduleW
                . '" height="' . $moduleH . '" fill="#000000"/>';
        }

        return $this->buildSvg($rowWidth, $svgHeight, $rects, $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Render one codeword as alternating bars derived from the byte value. */
    private function renderCw(int $byte, int $x, int $y, int $mw, int $mh): string
    {
        // Map byte to a simple 8-bar pattern by bit values (each bar = 1 or 2 modules wide)
        $rects = '';
        $cx    = $x;
        for ($bit = 7; $bit >= 0; $bit--) {
            $dark  = ($byte >> $bit) & 1;
            $width = $mw * ($dark ? 2 : 1);
            if ($dark) {
                $rects .= '<rect x="' . $cx . '" y="' . $y . '" width="' . $width
                    . '" height="' . $mh . '" fill="#000000"/>';
            }
            $cx += $width;
        }
        return $rects;
    }
}
