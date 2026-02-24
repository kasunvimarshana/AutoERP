<?php

namespace Modules\Logistics\Domain\Enums;

enum TrackingEventType: string
{
    case PICKED_UP        = 'picked_up';
    case IN_TRANSIT       = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED        = 'delivered';
    case FAILED_ATTEMPT   = 'failed_attempt';
    case RETURNED         = 'returned';
}
