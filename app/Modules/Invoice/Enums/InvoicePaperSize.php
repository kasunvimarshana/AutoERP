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

enum InvoicePaperSize: string
{
    case A4 = 'a4';
    case A5 = 'a5';
    case Thermal80mm = 'thermal_80mm';
    case Continuous85By55 = 'continuous_8_5_by_5_5';
    case Letter = 'letter';

    public function label(): string
    {
        return match ($this) {
            self::A4 => 'A4 (210 × 297 mm)',
            self::A5 => 'A5 (148 × 210 mm)',
            self::Thermal80mm => 'Thermal POS Receipt (80 mm)',
            self::Continuous85By55 => 'Continuous Feed (8.5 × 5.5 in)',
            self::Letter => 'US Letter (8.5 × 11 in)',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
