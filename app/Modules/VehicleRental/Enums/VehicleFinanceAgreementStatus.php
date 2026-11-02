<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleFinanceAgreementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Settled = 'settled';
    case Defaulted = 'defaulted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Settled => 'Settled',
            self::Defaulted => 'Defaulted',
            self::Cancelled => 'Cancelled',
        };
    }
}
