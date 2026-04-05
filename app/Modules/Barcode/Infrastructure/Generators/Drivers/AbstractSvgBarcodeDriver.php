<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeOutputFormat;
use Modules\Barcode\Infrastructure\Generators\BarcodeGeneratorDriverInterface;

/**
 * Base class for drivers that produce SVG bar/module output.
 *
 * Subclasses implement generateSvg() and may use buildSvg() for linear barcodes.
 * For all three output formats the SVG string is returned; callers may convert
 * it further (e.g. rasterise to PNG) at the application layer.
 */
abstract class AbstractSvgBarcodeDriver implements BarcodeGeneratorDriverInterface
{
    /**
     * Entry point – delegates to generateSvg() and returns the SVG for every
     * supported output format.
     */
    public function generate(string $value, string $format, array $options): string
    {
        return $this->generateSvg($value, $options);
    }

    /**
     * Produce the SVG markup for the given value.
     */
    abstract protected function generateSvg(string $value, array $options): string;

    // ── Protected helpers ─────────────────────────────────────────────────────

    /**
     * Assemble a complete SVG document for a linear (1-D) barcode.
     *
     * @param int    $totalWidth  Total width of the symbol in SVG user units.
     * @param int    $height      Bar height in SVG user units (quiet-zone excluded).
     * @param string $innerContent Pre-built SVG elements (the bar rectangles).
     * @param string $label       Human-readable text shown below the bars (empty = omit).
     * @param array  $options     Rendering hints forwarded by generate().
     */
    protected function buildSvg(
        int    $totalWidth,
        int    $height,
        string $innerContent,
        string $label = '',
        array  $options = [],
    ): string {
        $quietZone  = (int) ($options['quiet_zone'] ?? 10);
        $fontSize   = (int) ($options['font_size']  ?? 10);
        $svgWidth   = $totalWidth + 2 * $quietZone;

        $labelHeight = ($label !== '') ? $fontSize + 4 : 0;
        $svgHeight   = $height + $labelHeight + 4;

        $labelEl = '';
        if ($label !== '') {
            $cx       = (int) round($svgWidth / 2);
            $labelY   = $height + $fontSize + 2;
            $labelEl  = '<text x="' . $cx . '" y="' . $labelY
                . '" font-family="monospace" font-size="' . $fontSize
                . '" text-anchor="middle" fill="#000000">'
                . htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</text>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg"'
            . ' width="' . $svgWidth . '"'
            . ' height="' . $svgHeight . '"'
            . ' viewBox="0 0 ' . $svgWidth . ' ' . $svgHeight . '">'
            . '<rect width="' . $svgWidth . '" height="' . $svgHeight . '" fill="#FFFFFF"/>'
            . '<g transform="translate(' . $quietZone . ',2)">'
            . $innerContent
            . $labelEl
            . '</g>'
            . '</svg>';
    }

    /**
     * Build a sequence of SVG <rect> elements for alternating bar/space widths.
     *
     * @param int[] $widths    Bar and space widths, starting with a bar (dark).
     * @param int   $unitWidth Pixels per unit width.
     * @param int   $height    Bar height.
     * @return string          SVG <rect> elements.
     */
    protected function buildBars(array $widths, int $unitWidth, int $height): string
    {
        $rects = '';
        $x     = 0;
        $bar   = true; // start with a dark bar

        foreach ($widths as $w) {
            $px = $w * $unitWidth;
            if ($bar) {
                $rects .= '<rect x="' . $x . '" y="0" width="' . $px
                    . '" height="' . $height . '" fill="#000000"/>';
            }
            $x  += $px;
            $bar = !$bar;
        }

        return $rects;
    }
}
