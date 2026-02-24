<?php

namespace Modules\SubscriptionBilling\Domain\Enums;

enum BillingCycle: string
{
    case Monthly   = 'monthly';
    case Quarterly = 'quarterly';
    case Annually  = 'annually';
}
