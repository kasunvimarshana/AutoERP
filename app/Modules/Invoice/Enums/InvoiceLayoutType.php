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

enum InvoiceLayoutType: string
{
    case Preset = 'preset';
    case Blade = 'blade';

    public function label(): string
    {
        return match ($this) {
            self::Preset => 'Visual Preset',
            self::Blade => 'Custom Blade Template',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
