<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServicePaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
}
