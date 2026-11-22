<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

enum InvoiceOrientation: string
{
    case Portrait = 'portrait';
    case Landscape = 'landscape';

    public function label(): string
    {
        return match ($this) {
            self::Portrait => 'Portrait',
            self::Landscape => 'Landscape',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
