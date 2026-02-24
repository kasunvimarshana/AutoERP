<?php

namespace Modules\SubscriptionBilling\Domain\Enums;

enum SubscriptionStatus: string
{
    case Trial    = 'trial';
    case Active   = 'active';
    case Paused   = 'paused';
    case Cancelled = 'cancelled';
    case Expired  = 'expired';
}
