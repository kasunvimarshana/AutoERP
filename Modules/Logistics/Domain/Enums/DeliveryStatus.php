<?php

namespace Modules\Logistics\Domain\Enums;

enum DeliveryStatus: string
{
    case PENDING    = 'pending';
    case DISPATCHED = 'dispatched';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED  = 'delivered';
    case FAILED     = 'failed';
    case CANCELLED  = 'cancelled';
}
