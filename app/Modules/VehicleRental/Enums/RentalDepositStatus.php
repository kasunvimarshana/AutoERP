<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalDepositStatus: string
{
    case Pending = 'pending';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case PartiallyApplied = 'partially_applied';
    case Refunded = 'refunded';
    case Forfeited = 'forfeited';
    case Closed = 'closed';
}
