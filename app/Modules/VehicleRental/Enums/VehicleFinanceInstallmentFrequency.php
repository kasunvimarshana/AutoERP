<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleFinanceInstallmentFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}
