<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoicePrintLayout: string
{
    case StandardA4 = 'standard_a4';
    case CompactA5 = 'compact_a5';

    public const CONFIGURATION_KEY = 'invoice.default_print_layout';

    public function paperSize(): string
    {
        return match ($this) {
            self::StandardA4 => 'A4',
            self::CompactA5 => 'A5',
        };
    }

    public function orientation(): string
    {
        return match ($this) {
            self::StandardA4 => 'portrait',
            self::CompactA5 => 'landscape',
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::StandardA4 => 'layout-a4',
            self::CompactA5 => 'layout-a5',
        };
    }
}
