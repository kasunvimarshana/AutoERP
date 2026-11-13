<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BillingCycleItemType: string
{
    case Addition = 'addition';
    case Deduction = 'deduction';
}
