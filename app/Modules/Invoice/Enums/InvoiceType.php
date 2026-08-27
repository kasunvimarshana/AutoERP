<?php

declare(strict_types=1);

namespace Modules\Invoice\Enums;

use LogicException;

enum InvoiceType: string
{
    case Purchase = 'purchase';
    case Sales = 'sales';
    case Service = 'service';
    case Rental = 'rental';
    case VehicleFinance = 'vehicle_finance';
    case Manual = 'manual';
    case Credit = 'credit';
    case Debit = 'debit';

    public function belongsToRetiredSourceModule(): bool
    {
        return in_array($this, [self::Rental, self::VehicleFinance], true);
    }

    public function retiredSourceLabel(): string
    {
        return match ($this) {
            self::Rental => 'Rental',
            self::VehicleFinance => 'Vehicle Finance',
            default => throw new LogicException("Invoice type [{$this->value}] does not belong to a retired source module."),
        };
    }
}
