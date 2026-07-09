<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceBillingStatus: string
{
    case Unbilled = 'unbilled';
    case PartiallyBilled = 'partially_billed';
    case Billed = 'billed';
}
