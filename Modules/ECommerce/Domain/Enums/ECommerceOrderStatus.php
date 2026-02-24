<?php

namespace Modules\ECommerce\Domain\Enums;

enum ECommerceOrderStatus: string
{
    case Pending    = 'pending';
    case Confirmed  = 'confirmed';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';
}
