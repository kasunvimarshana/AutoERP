<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * Aztec code driver (simplified visual stub).
 *
 * Renders an Aztec-like concentric square "bull's eye" finder pattern
 * surrounded by data modules derived from the encoded value.
 * This is a structural stub; for production use a dedicated library.
 */
class AztecDriver extends AbstractSvgBarcodeDriver
{
    public function supports(string $type): bool
    {
        return $type === BarcodeType::AZTEC;
    }

    public function validate(string $value): bool
    {
        return $value !== '';
    }

    protected function generateSvg(string $value, array $options): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Aztec: value must not be empty.');
        }

        $moduleSize = (int) ($options['module_size'] ?? 4);
        $label      = $options['label'] ?? '';

        // Grid size: must be odd, at least 15.
        $dataLen  = strlen($value);
        $gridSize = max(15, $dataLen + 13);
        if ($gridSize % 2 === 0) {
            $gridSize++;
        }

        $bytes   = array_map('ord', str_split($value));
        $grid    = $this->buildGrid($gridSize, $bytes);

        $svgSize = $gridSize * $moduleSize;
        $rects   = '';
        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                if ($grid[$r][$c]) {
                    $rects .= '<rect x="' . ($c * $moduleSize) . '" y="' . ($r * $moduleSize)
                        . '" width="' . $moduleSize . '" height="' . $moduleSize
                        . '" fill="#000000"/>';
                }
            }
        }

        return $this->buildSvg($svgSize, $svgSize, $rects, $label, $options);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildGrid(int $size, array $bytes): array
    {
        $grid   = array_fill(0, $size, array_fill(0, $size, 0));
        $center = intdiv($size, 2);

        // Bull's-eye finder: concentric filled/empty rings.
        for ($ring = 0; $ring <= 5; $ring++) {
            $filled = ($ring % 2 === 0);
            for ($r = $center - $ring; $r <= $center + $ring; $r++) {
                for ($c = $center - $ring; $c <= $center + $ring; $c++) {
                    if ($r === $center - $ring || $r === $center + $ring
                        || $c === $center - $ring || $c === $center + $ring) {
                        $grid[$r][$c] = $filled ? 1 : 0;
                    }
                }
            }
        }

        // Mode message ring (one ring outside the bull's-eye) – alternating
        $modeRing = 6;
        for ($r = $center - $modeRing; $r <= $center + $modeRing; $r++) {
            for ($c = $center - $modeRing; $c <= $center + $modeRing; $c++) {
                if ($r === $center - $modeRing || $r === $center + $modeRing
                    || $c === $center - $modeRing || $c === $center + $modeRing) {
                    $grid[$r][$c] = (($r + $c) % 2 === 0) ? 1 : 0;
                }
            }
        }

        // Fill data area outside the bull's-eye with encoded bits.
        $bit     = 0;
        $byteIdx = 0;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                // Skip the finder/mode area.
                if (abs($r - $center) <= $modeRing && abs($c - $center) <= $modeRing) {
                    continue;
                }
                if ($byteIdx < count($bytes)) {
                    $grid[$r][$c] = ($bytes[$byteIdx] >> (7 - ($bit % 8))) & 1;
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
