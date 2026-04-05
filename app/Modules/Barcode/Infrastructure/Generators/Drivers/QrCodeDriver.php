<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators\Drivers;

use Modules\Barcode\Domain\ValueObjects\BarcodeType;

/**
 * QR Code driver — pure PHP, byte mode, Error Correction Level M.
 *
 * Produces scannable QR codes for data lengths covered by versions 1–10.
 * Implements:
 *  - Byte-mode encoding
 *  - Reed-Solomon error correction over GF(256)
 *  - Block interleaving
 *  - Finder / timing / alignment / format-info / version-info placement
 *  - All 8 mask patterns with ISO penalty scoring
 */
class QrCodeDriver extends AbstractSvgBarcodeDriver
{
    // ── Symbology constants ───────────────────────────────────────────────────

    private const ECL_BITS = 0b00; // Error Correction Level M = 00

    /**
     * QR byte-mode capacity and block structure for ECL M.
     * blocks entry format: [numBlocks, ecCodewordsPerBlock, dataCodewordsPerBlock]
     *
     * @var array<int, array{size:int, cap:int, blocks:array<array{0:int,1:int,2:int}>}>
     */
    private const VERSIONS = [
        1  => ['size' => 21, 'cap' => 14,  'blocks' => [[1, 10, 16]]],
        2  => ['size' => 25, 'cap' => 26,  'blocks' => [[1, 16, 28]]],
        3  => ['size' => 29, 'cap' => 42,  'blocks' => [[1, 26, 44]]],
        4  => ['size' => 33, 'cap' => 62,  'blocks' => [[2, 18, 32]]],
        5  => ['size' => 37, 'cap' => 84,  'blocks' => [[2, 24, 43]]],
        6  => ['size' => 41, 'cap' => 106, 'blocks' => [[4, 16, 27]]],
        7  => ['size' => 45, 'cap' => 122, 'blocks' => [[4, 18, 31]]],
        8  => ['size' => 49, 'cap' => 152, 'blocks' => [[2, 22, 38], [2, 22, 39]]],
        9  => ['size' => 53, 'cap' => 180, 'blocks' => [[3, 20, 36], [2, 20, 37]]],
        10 => ['size' => 57, 'cap' => 197, 'blocks' => [[4, 24, 43], [1, 24, 43]]],
    ];

    /**
     * Alignment pattern centre positions (row and column values) per version.
     * All combinations of these coordinates are used except where they would
     * overlap finder patterns.
     *
     * @var array<int, int[]>
     */
    private const ALIGN_COORDS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    // GF(256) log/exp tables, initialised in __construct.
    /** @var int[] */
    private array $gfExp = [];
    /** @var int[] */
    private array $gfLog = [];

    public function __construct()
    {
        $this->buildGFTables();
    }

    // ── BarcodeGeneratorDriverInterface ───────────────────────────────────────

    public function supports(string $type): bool
    {
        return $type === BarcodeType::QR;
    }

    public function validate(string $value): bool
    {
        return $value !== '' && strlen($value) <= self::VERSIONS[10]['cap'];
    }

    protected function generateSvg(string $value, array $options): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException('QR: value must not be empty.');
        }

        $moduleSize = max(1, (int) ($options['module_size'] ?? 4));
        $quietModules = (int) ($options['quiet_modules'] ?? 4);
        $label = $options['label'] ?? '';

        $version = $this->selectVersion($value);
        if ($version === 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'QR: value (%d bytes) exceeds maximum for supported versions 1–10 (%d bytes).',
                    strlen($value),
                    self::VERSIONS[10]['cap']
                )
            );
        }

        $bits      = $this->buildBitStream($value, $version);
        $codewords = $this->buildCodewords($bits, $version);
        $size      = self::VERSIONS[$version]['size'];
        $matrix    = $this->buildMatrix($version, $size, $codewords);

        // Render SVG
        $totalModules = $size + 2 * $quietModules;
        $svgPx        = $totalModules * $moduleSize;

        $rects = '';
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $px = ($c + $quietModules) * $moduleSize;
                    $py = ($r + $quietModules) * $moduleSize;
                    $rects .= '<rect x="' . $px . '" y="' . $py
                        . '" width="' . $moduleSize . '" height="' . $moduleSize
                        . '" fill="#000000"/>';
                }
            }
        }

        return $this->buildSvg($svgPx, $svgPx, $rects, $label, $options);
    }

    // ── Version selection ─────────────────────────────────────────────────────

    private function selectVersion(string $value): int
    {
        $len = strlen($value);
        foreach (self::VERSIONS as $v => $info) {
            if ($len <= $info['cap']) {
                return $v;
            }
        }
        return 0;
    }

    // ── Bit-stream construction (byte mode) ───────────────────────────────────

    /**
     * @return int[] Array of bits (0/1)
     */
    private function buildBitStream(string $value, int $version): array
    {
        $bits    = [];
        $vInfo   = self::VERSIONS[$version];

        // Mode indicator: byte mode = 0b0100
        $this->appendBits($bits, 0b0100, 4);

        // Character count indicator: 8 bits for V1-9, 16 for V10-26
        $ccBits = ($version <= 9) ? 8 : 16;
        $this->appendBits($bits, strlen($value), $ccBits);

        // Encode each byte
        foreach (str_split($value) as $ch) {
            $this->appendBits($bits, ord($ch), 8);
        }

        // Total data codeword capacity
        $totalDataBytes = 0;
        foreach ($vInfo['blocks'] as [$count, , $dataLen]) {
            $totalDataBytes += $count * $dataLen;
        }
        $totalBits = $totalDataBytes * 8;

        // Terminator (up to 4 zero bits)
        $remaining = $totalBits - count($bits);
        $termLen   = min(4, max(0, $remaining));
        for ($i = 0; $i < $termLen; $i++) {
            $bits[] = 0;
        }

        // Bit-pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Pad codewords 0xEC / 0x11
        $padBytes = [0xEC, 0x11];
        $padIdx   = 0;
        while (count($bits) < $totalBits) {
            $this->appendBits($bits, $padBytes[$padIdx % 2], 8);
            $padIdx++;
        }

        return $bits;
    }

    private function appendBits(array &$bits, int $value, int $count): void
    {
        for ($i = $count - 1; $i >= 0; $i--) {
            $bits[] = ($value >> $i) & 1;
        }
    }

    // ── Error correction and interleaving ─────────────────────────────────────

    /**
     * Convert bits to codewords, generate EC blocks, and interleave.
     *
     * @param  int[] $bits
     * @return int[] Final interleaved codeword sequence
     */
    private function buildCodewords(array $bits, int $version): array
    {
        // Convert bit array to bytes
        $dataCodewords = [];
        for ($i = 0, $n = count($bits); $i < $n; $i += 8) {
            $byte = 0;
            for ($j = 0; $j < 8; $j++) {
                $byte = ($byte << 1) | ($bits[$i + $j] ?? 0);
            }
            $dataCodewords[] = $byte;
        }

        // Build blocks
        $blockGroups = self::VERSIONS[$version]['blocks'];
        $blocks      = [];
        $offset      = 0;

        foreach ($blockGroups as [$count, $ecLen, $dataLen]) {
            for ($b = 0; $b < $count; $b++) {
                $blockData = array_slice($dataCodewords, $offset, $dataLen);
                $blockEc   = $this->rsEncode($blockData, $ecLen);
                $blocks[]  = ['data' => $blockData, 'ec' => $blockEc];
                $offset   += $dataLen;
            }
        }

        // Interleave data codewords column-wise
        $result  = [];
        $maxData = max(array_map(static fn($b) => count($b['data']), $blocks));
        for ($i = 0; $i < $maxData; $i++) {
            foreach ($blocks as $block) {
                if (isset($block['data'][$i])) {
                    $result[] = $block['data'][$i];
                }
            }
        }

        // Interleave EC codewords column-wise
        $maxEc = max(array_map(static fn($b) => count($b['ec']), $blocks));
        for ($i = 0; $i < $maxEc; $i++) {
            foreach ($blocks as $block) {
                if (isset($block['ec'][$i])) {
                    $result[] = $block['ec'][$i];
                }
            }
        }

        return $result;
    }

    // ── Reed-Solomon encoding ─────────────────────────────────────────────────

    /**
     * Encode data with Reed-Solomon over GF(256).
     *
     * @param  int[] $data
     * @param  int   $ecLen Number of EC codewords
     * @return int[]        Error-correction codewords
     */
    private function rsEncode(array $data, int $ecLen): array
    {
        $gen = $this->rsGenerator($ecLen);
        $msg = array_merge($data, array_fill(0, $ecLen, 0));

        for ($i = 0, $dataLen = count($data); $i < $dataLen; $i++) {
            $coeff = $msg[$i];
            if ($coeff === 0) {
                continue;
            }
            $logCoeff = $this->gfLog[$coeff];
            for ($j = 0; $j <= $ecLen; $j++) {
                if ($gen[$j] !== 0) {
                    $msg[$i + $j] ^= $this->gfExp[($logCoeff + $this->gfLog[$gen[$j]]) % 255];
                }
            }
        }

        return array_slice($msg, count($data));
    }

    /**
     * Build the Reed-Solomon generator polynomial for $ecLen EC codewords.
     *
     * @return int[] Polynomial coefficients in GF(256), degree $ecLen
     */
    private function rsGenerator(int $ecLen): array
    {
        $gen = [1];
        for ($i = 0; $i < $ecLen; $i++) {
            $gen = $this->gfPolyMul($gen, [1, $this->gfExp[$i]]);
        }
        return $gen;
    }

    /**
     * @param  int[] $a
     * @param  int[] $b
     * @return int[]
     */
    private function gfPolyMul(array $a, array $b): array
    {
        $result = array_fill(0, count($a) + count($b) - 1, 0);
        foreach ($a as $i => $ai) {
            foreach ($b as $j => $bj) {
                $result[$i + $j] ^= $this->gfMul($ai, $bj);
            }
        }
        return $result;
    }

    private function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return $this->gfExp[($this->gfLog[$a] + $this->gfLog[$b]) % 255];
    }

    /**
     * Build GF(256) exp and log tables.
     * Primitive polynomial: x^8 + x^4 + x^3 + x^2 + 1 = 0x11D.
     */
    private function buildGFTables(): void
    {
        $this->gfExp = array_fill(0, 512, 0);
        $this->gfLog = array_fill(0, 256, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $this->gfExp[$i] = $x;
            $this->gfLog[$x] = $i;
            $x <<= 1;
            if ($x >= 256) {
                $x ^= 0x11D;
            }
        }
        // Extend exp table to 510 to simplify mod-255 arithmetic
        for ($i = 255; $i < 512; $i++) {
            $this->gfExp[$i] = $this->gfExp[$i - 255];
        }
    }

    // ── Matrix construction ───────────────────────────────────────────────────

    /**
     * @param  int[]  $codewords
     * @return int[][] 2-D array, 0 = light, 1 = dark
     */
    private function buildMatrix(int $version, int $size, array $codewords): array
    {
        // $matrix: null = data position, 0/1 = function pattern
        $matrix   = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        $this->placeFinders($matrix, $reserved, $size);
        $this->placeTimingPatterns($matrix, $reserved, $size);
        $this->placeAlignmentPatterns($matrix, $reserved, $version);
        $this->placeDarkModule($matrix, $reserved, $version);
        $this->reserveFormatAreas($matrix, $reserved, $size);
        if ($version >= 7) {
            $this->reserveVersionAreas($matrix, $reserved, $size);
        }

        $this->placeDataBits($matrix, $reserved, $size, $codewords);

        // Try all 8 masks and pick the one with the lowest penalty score
        $bestMask    = 0;
        $bestPenalty = PHP_INT_MAX;
        $bestMatrix  = null;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = $this->applyMask($matrix, $reserved, $size, $mask);
            $this->placeFormatInfo($candidate, $size, $mask);
            if ($version >= 7) {
                $this->placeVersionInfo($candidate, $version, $size);
            }
            $penalty = $this->calcPenalty($candidate, $size);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask    = $mask;
                $bestMatrix  = $candidate;
            }
        }

        // $bestMatrix contains null only if something went very wrong; default to 0
        return array_map(
            static fn($row) => array_map(static fn($v) => $v ?? 0, $row),
            $bestMatrix
        );
    }

    // ── Finder patterns ───────────────────────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeFinders(array &$matrix, array &$reserved, int $size): void
    {
        $this->placeFinder($matrix, $reserved, 0, 0);
        $this->placeFinder($matrix, $reserved, 0, $size - 7);
        $this->placeFinder($matrix, $reserved, $size - 7, 0);

        // Separators (one-module-wide light border adjacent to each finder)
        $this->placeHLine($matrix, $reserved, 7, 0, 8, 0);
        $this->placeVLine($matrix, $reserved, 0, 7, 8, 0);
        $this->placeHLine($matrix, $reserved, 7, $size - 8, 8, 0);
        $this->placeVLine($matrix, $reserved, 0, $size - 8, 8, 0);
        $this->placeHLine($matrix, $reserved, $size - 8, 0, 8, 0);
        $this->placeVLine($matrix, $reserved, $size - 8, 7, 8, 0);
    }

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeFinder(array &$matrix, array &$reserved, int $r0, int $c0): void
    {
        static $pat = [
            [1,1,1,1,1,1,1],
            [1,0,0,0,0,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,0,0,0,0,1],
            [1,1,1,1,1,1,1],
        ];
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $matrix[$r0 + $r][$c0 + $c]   = $pat[$r][$c];
                $reserved[$r0 + $r][$c0 + $c] = true;
            }
        }
    }

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeHLine(array &$matrix, array &$reserved, int $row, int $colStart, int $len, int $val): void
    {
        for ($c = $colStart; $c < $colStart + $len; $c++) {
            if ($row >= 0 && $row < count($matrix) && $c >= 0 && $c < count($matrix[0])) {
                $matrix[$row][$c]   = $val;
                $reserved[$row][$c] = true;
            }
        }
    }

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeVLine(array &$matrix, array &$reserved, int $rowStart, int $col, int $len, int $val): void
    {
        for ($r = $rowStart; $r < $rowStart + $len; $r++) {
            if ($r >= 0 && $r < count($matrix) && $col >= 0 && $col < count($matrix[0])) {
                $matrix[$r][$col]   = $val;
                $reserved[$r][$col] = true;
            }
        }
    }

    // ── Timing patterns ───────────────────────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeTimingPatterns(array &$matrix, array &$reserved, int $size): void
    {
        for ($i = 8; $i < $size - 8; $i++) {
            $v = ($i % 2 === 0) ? 1 : 0;
            if (!$reserved[6][$i]) {
                $matrix[6][$i]   = $v;
                $reserved[6][$i] = true;
            }
            if (!$reserved[$i][6]) {
                $matrix[$i][6]   = $v;
                $reserved[$i][6] = true;
            }
        }
    }

    // ── Alignment patterns ────────────────────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeAlignmentPatterns(array &$matrix, array &$reserved, int $version): void
    {
        $coords = self::ALIGN_COORDS[$version];
        $n      = count($coords);
        if ($n === 0) {
            return;
        }
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $cr = $coords[$i];
                $cc = $coords[$j];
                // Skip if centre already reserved (overlaps a finder)
                if ($reserved[$cr][$cc]) {
                    continue;
                }
                $this->placeAlignment($matrix, $reserved, $cr, $cc);
            }
        }
    }

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeAlignment(array &$matrix, array &$reserved, int $cr, int $cc): void
    {
        static $pat = [
            [1,1,1,1,1],
            [1,0,0,0,1],
            [1,0,1,0,1],
            [1,0,0,0,1],
            [1,1,1,1,1],
        ];
        for ($dr = -2; $dr <= 2; $dr++) {
            for ($dc = -2; $dc <= 2; $dc++) {
                $matrix[$cr + $dr][$cc + $dc]   = $pat[$dr + 2][$dc + 2];
                $reserved[$cr + $dr][$cc + $dc] = true;
            }
        }
    }

    // ── Dark module ───────────────────────────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function placeDarkModule(array &$matrix, array &$reserved, int $version): void
    {
        $r = 4 * $version + 9;
        $c = 8;
        $matrix[$r][$c]   = 1;
        $reserved[$r][$c] = true;
    }

    // ── Format info reservation ───────────────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function reserveFormatAreas(array &$matrix, array &$reserved, int $size): void
    {
        // Copy 1: row 8 (cols 0-8) and col 8 (rows 0-8)
        for ($i = 0; $i < 9; $i++) {
            if (!$reserved[8][$i]) {
                $matrix[8][$i]   = 0;
                $reserved[8][$i] = true;
            }
            if (!$reserved[$i][8]) {
                $matrix[$i][8]   = 0;
                $reserved[$i][8] = true;
            }
        }
        // Copy 2 top-right: row 8, cols size-8 to size-1
        for ($c = $size - 8; $c < $size; $c++) {
            if (!$reserved[8][$c]) {
                $matrix[8][$c]   = 0;
                $reserved[8][$c] = true;
            }
        }
        // Copy 2 bottom-left: col 8, rows size-7 to size-1
        for ($r = $size - 7; $r < $size; $r++) {
            if (!$reserved[$r][8]) {
                $matrix[$r][8]   = 0;
                $reserved[$r][8] = true;
            }
        }
    }

    // ── Version info reservation (V7+) ───────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved */
    private function reserveVersionAreas(array &$matrix, array &$reserved, int $size): void
    {
        for ($r = 0; $r < 6; $r++) {
            for ($c = 0; $c < 3; $c++) {
                $matrix[$r][$size - 11 + $c]   = 0;
                $reserved[$r][$size - 11 + $c] = true;
                $matrix[$size - 11 + $c][$r]   = 0;
                $reserved[$size - 11 + $c][$r] = true;
            }
        }
    }

    // ── Data bit placement ────────────────────────────────────────────────────

    /** @param int[][] $matrix @param bool[][] $reserved @param int[] $codewords */
    private function placeDataBits(array &$matrix, array &$reserved, int $size, array $codewords): void
    {
        // Convert codewords to a flat bit array
        $bits = [];
        foreach ($codewords as $cw) {
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($cw >> $b) & 1;
            }
        }
        // Remainder bits (all 0) – required for some versions
        $totalModules = $size * $size;
        $funcCount    = 0;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($reserved[$r][$c]) {
                    $funcCount++;
                }
            }
        }
        $remainderBits = $totalModules - $funcCount - count($bits);
        for ($i = 0; $i < $remainderBits; $i++) {
            $bits[] = 0;
        }

        $bitIdx = 0;
        $up     = true;
        $col    = $size - 1;

        while ($col > 0) {
            if ($col === 6) {
                $col--; // skip timing column
            }
            for ($i = 0; $i < $size; $i++) {
                $row = $up ? ($size - 1 - $i) : $i;
                // Right column of the two-column strip
                if (!$reserved[$row][$col]) {
                    $matrix[$row][$col] = $bits[$bitIdx] ?? 0;
                    $bitIdx++;
                }
                // Left column of the strip
                if ($col > 0 && !$reserved[$row][$col - 1]) {
                    $matrix[$row][$col - 1] = $bits[$bitIdx] ?? 0;
                    $bitIdx++;
                }
            }
            $col -= 2;
            $up   = !$up;
        }
    }

    // ── Masking ───────────────────────────────────────────────────────────────

    /**
     * @param  int[][]  $matrix
     * @param  bool[][] $reserved
     * @return int[][]
     */
    private function applyMask(array $matrix, array $reserved, int $size, int $mask): array
    {
        $result = $matrix;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($reserved[$r][$c]) {
                    continue;
                }
                if ($result[$r][$c] !== null && $this->maskCondition($r, $c, $mask)) {
                    $result[$r][$c] ^= 1;
                }
            }
        }
        return $result;
    }

    private function maskCondition(int $r, int $c, int $mask): bool
    {
        return match ($mask) {
            0 => ($r + $c) % 2 === 0,
            1 => $r % 2 === 0,
            2 => $c % 3 === 0,
            3 => ($r + $c) % 3 === 0,
            4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
            5 => (($r * $c) % 2 + ($r * $c) % 3) === 0,
            6 => (($r * $c) % 2 + ($r * $c) % 3) % 2 === 0,
            7 => (($r + $c) % 2 + ($r * $c) % 3) % 2 === 0,
            default => false,
        };
    }

    // ── Format information placement ──────────────────────────────────────────

    /** @param int[][] $matrix */
    private function placeFormatInfo(array &$matrix, int $size, int $mask): void
    {
        $formatBits = $this->calcFormatBits($mask);

        // Copy 1: bit i at positions listed below (bit 0 = LSB)
        $positions = [
            [8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],
            [7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8],
        ];
        for ($i = 0; $i < 15; $i++) {
            [$r, $c] = $positions[$i];
            $matrix[$r][$c] = ($formatBits >> $i) & 1;
        }

        // Copy 2: top-right (row 8) and bottom-left (col 8)
        // Top-right: bits 7-14 at row 8, cols size-1 down to size-8
        for ($i = 0; $i < 8; $i++) {
            $matrix[8][$size - 1 - $i] = ($formatBits >> $i) & 1;
        }
        // Bottom-left: bits 8-14 at col 8, rows size-7 up to size-1
        for ($i = 0; $i < 7; $i++) {
            $matrix[$size - 7 + $i][8] = ($formatBits >> (8 + $i)) & 1;
        }
    }

    /**
     * Compute the 15-bit format information word for ECL M and the given mask.
     *
     * Format data = (ecl_bits << 3) | mask_pattern  (5 bits)
     * BCH generator: x^10 + x^8 + x^5 + x^4 + x^2 + x + 1 = 0x537
     * Mask constant: 101010000010010 = 0x5412
     */
    private function calcFormatBits(int $mask): int
    {
        $data = (self::ECL_BITS << 3) | $mask;
        $rem  = $data << 10;
        // Polynomial division: degree of generator = 10, so loop from bit 14 down to 10
        for ($i = 4; $i >= 0; $i--) {
            if (($rem >> ($i + 10)) & 1) {
                $rem ^= 0x537 << $i;
            }
        }
        $formatBits = ($data << 10) | ($rem & 0x3FF);
        return $formatBits ^ 0x5412;
    }

    // ── Version information placement (V7+) ───────────────────────────────────

    /** @param int[][] $matrix */
    private function placeVersionInfo(array &$matrix, int $version, int $size): void
    {
        $versionBits = $this->calcVersionBits($version);

        for ($i = 0; $i < 18; $i++) {
            $bit = ($versionBits >> $i) & 1;
            $r   = intdiv($i, 3);
            $c   = $i % 3;
            // Upper-right block
            $matrix[$r][$size - 11 + $c] = $bit;
            // Lower-left block
            $matrix[$size - 11 + $c][$r] = $bit;
        }
    }

    /**
     * Compute the 18-bit version information word.
     * BCH generator: x^12+x^11+x^10+x^9+x^8+x^5+x^2+1 = 0x1F25
     */
    private function calcVersionBits(int $version): int
    {
        $data = $version << 12;
        for ($i = 5; $i >= 0; $i--) {
            if (($data >> ($i + 12)) & 1) {
                $data ^= 0x1F25 << $i;
            }
        }
        return ($version << 12) | ($data & 0xFFF);
    }

    // ── Mask penalty scoring ──────────────────────────────────────────────────

    /**
     * Calculate the ISO 18004 penalty score for a candidate matrix.
     *
     * @param int[][] $matrix
     */
    private function calcPenalty(array $matrix, int $size): int
    {
        $penalty = 0;

        // Rule 1: 5+ consecutive modules of the same colour in a row/column
        for ($r = 0; $r < $size; $r++) {
            $penalty += $this->penaltyRun($matrix[$r]);
        }
        for ($c = 0; $c < $size; $c++) {
            $col = [];
            for ($r = 0; $r < $size; $r++) {
                $col[] = $matrix[$r][$c] ?? 0;
            }
            $penalty += $this->penaltyRun($col);
        }

        // Rule 2: 2×2 blocks of the same colour
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $v = $matrix[$r][$c] ?? 0;
                if (($matrix[$r][$c + 1] ?? 0) === $v
                    && ($matrix[$r + 1][$c] ?? 0) === $v
                    && ($matrix[$r + 1][$c + 1] ?? 0) === $v
                ) {
                    $penalty += 3;
                }
            }
        }

        // Rule 3: specific patterns (1011101 flanked by 0000 or 0000 flanked by 1011101)
        $p3 = [1,0,1,1,1,0,1,0,0,0,0];
        $p3r = array_reverse($p3);
        for ($r = 0; $r < $size; $r++) {
            $row = array_map(static fn($v) => $v ?? 0, $matrix[$r]);
            $penalty += $this->penaltyPattern($row, $p3) * 40;
            $penalty += $this->penaltyPattern($row, $p3r) * 40;
        }
        for ($c = 0; $c < $size; $c++) {
            $col = [];
            for ($r = 0; $r < $size; $r++) {
                $col[] = $matrix[$r][$c] ?? 0;
            }
            $penalty += $this->penaltyPattern($col, $p3) * 40;
            $penalty += $this->penaltyPattern($col, $p3r) * 40;
        }

        // Rule 4: proportion of dark modules
        $dark  = 0;
        $total = $size * $size;
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                $dark += ($matrix[$r][$c] ?? 0);
            }
        }
        $pct      = (int) (abs($dark * 100 / $total - 50) / 5) * 5;
        $penalty += ($pct / 5) * 10;

        return (int) $penalty;
    }

    /** @param int[] $modules */
    private function penaltyRun(array $modules): int
    {
        $penalty = 0;
        $run     = 1;
        $prev    = $modules[0] ?? 0;
        $n       = count($modules);
        for ($i = 1; $i < $n; $i++) {
            $cur = $modules[$i] ?? 0;
            if ($cur === $prev) {
                $run++;
                if ($run === 5) {
                    $penalty += 3;
                } elseif ($run > 5) {
                    $penalty++;
                }
            } else {
                $run  = 1;
                $prev = $cur;
            }
        }
        return $penalty;
    }

    /** @param int[] $modules @param int[] $pattern */
    private function penaltyPattern(array $modules, array $pattern): int
    {
        $count  = 0;
        $pLen   = count($pattern);
        $mLen   = count($modules);
        for ($i = 0; $i <= $mLen - $pLen; $i++) {
            $match = true;
            for ($j = 0; $j < $pLen; $j++) {
                if ($modules[$i + $j] !== $pattern[$j]) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                $count++;
            }
        }
        return $count;
    }
}
