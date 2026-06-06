<?php

declare(strict_types=1);

namespace Modules\Customer\Enums;

enum CustomerAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Registered = 'registered';
    case Service = 'service';
    case Other = 'other';
}
