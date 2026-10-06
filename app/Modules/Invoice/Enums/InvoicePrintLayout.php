<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoicePrintLayout: string
{
    case StandardA4 = 'standard_a4';
    case CompactA5 = 'compact_a5';
    case Continuous85By55 = 'continuous_8_5_by_5_5';

    public const CONFIGURATION_KEY = 'invoice.default_print_layout';

    private const CONTINUOUS_WIDTH_MM = 215.9;

    private const CONTINUOUS_HEIGHT_MM = 139.7;

    private const MILLIMETERS_PER_INCH = 25.4;

    private const POINTS_PER_INCH = 72;

    private const CONTINUOUS_SIDE_MARGIN_MM = 13;

    private const CONTINUOUS_END_MARGIN_MM = 4.2;

    public function paperSize(): string
    {
        return match ($this) {
            self::StandardA4 => 'A4',
            self::CompactA5 => 'A5',
            self::Continuous85By55 => self::CONTINUOUS_WIDTH_MM.'mm '.self::CONTINUOUS_HEIGHT_MM.'mm',
        };
    }

    public function orientation(): string
    {
        return match ($this) {
            self::StandardA4 => 'portrait',
            self::CompactA5 => 'portrait',
            self::Continuous85By55 => 'landscape',
        };
    }

    public function cssPageSize(): string
    {
        return match ($this) {
            self::StandardA4 => 'A4 portrait',
            self::CompactA5 => 'A5 portrait',
            self::Continuous85By55 => $this->paperSize(),
        };
    }

    public function cssPageMargin(): string
    {
        return match ($this) {
            self::StandardA4 => '10mm',
            self::CompactA5 => '6mm',
            self::Continuous85By55 => self::CONTINUOUS_END_MARGIN_MM.'mm '.self::CONTINUOUS_SIDE_MARGIN_MM.'mm',
        };
    }

    public function continuousCssWidth(): string
    {
        return self::CONTINUOUS_WIDTH_MM.'mm';
    }

    public function continuousCssHeight(): string
    {
        return self::CONTINUOUS_HEIGHT_MM.'mm';
    }

    public function continuousCssHorizontalMargin(): string
    {
        return self::CONTINUOUS_SIDE_MARGIN_MM.'mm';
    }

    public function continuousCssVerticalMargin(): string
    {
        return self::CONTINUOUS_END_MARGIN_MM.'mm';
    }

    /** @return string|array{0: float, 1: float, 2: float, 3: float} */
    public function pdfPaper(): string|array
    {
        if ($this !== self::Continuous85By55) {
            return $this->paperSize();
        }

        return [
            0.0,
            0.0,
            self::CONTINUOUS_HEIGHT_MM * self::POINTS_PER_INCH / self::MILLIMETERS_PER_INCH,
            self::CONTINUOUS_WIDTH_MM * self::POINTS_PER_INCH / self::MILLIMETERS_PER_INCH,
        ];
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::StandardA4 => 'layout-a4',
            self::CompactA5 => 'layout-a5',
            self::Continuous85By55 => 'layout-continuous',
        };
    }

    public function isCompact(): bool
    {
        return $this !== self::StandardA4;
    }
}
