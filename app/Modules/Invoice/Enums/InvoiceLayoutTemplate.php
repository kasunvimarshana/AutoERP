<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Enums;

enum InvoiceLayoutTemplate: string
{
    case Standard = 'invoice.layouts.standard';
    case Modern = 'invoice.layouts.modern';
    case Compact = 'invoice.layouts.compact';
    case Thermal = 'invoice.layouts.thermal';
    case Continuous = 'invoice.layouts.continuous';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard Classic A4',
            self::Modern => 'Modern Professional A4',
            self::Compact => 'Compact Slip A5',
            self::Thermal => 'Thermal POS Receipt 80mm',
            self::Continuous => 'Continuous Feed 8.5 × 5.5',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
