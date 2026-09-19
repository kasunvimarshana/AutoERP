<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoicePrintLayout: string
{
    case StandardA4 = 'standard_a4';
    case CompactA5 = 'compact_a5';
    case CompactA5Portrait = 'compact_a5_portrait';

    public const CONFIGURATION_KEY = 'invoice.default_print_layout';

    public function paperSize(): string
    {
        return match ($this) {
            self::StandardA4 => 'A4',
            self::CompactA5, self::CompactA5Portrait => 'A5',
        };
    }

    public function orientation(): string
    {
        return match ($this) {
            self::StandardA4 => 'portrait',
            self::CompactA5 => 'landscape',
            self::CompactA5Portrait => 'portrait',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::StandardA4 => 'layout-a4',
            self::CompactA5 => 'layout-a5',
            self::CompactA5Portrait => 'layout-a5 layout-a5-portrait',
        };
    }

    public function isCompact(): bool
    {
        return $this !== self::StandardA4;
    }
}
