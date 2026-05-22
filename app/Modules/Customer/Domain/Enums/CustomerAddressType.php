<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Enums;

enum CustomerAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Office = 'office';
    case Other = 'other';
}