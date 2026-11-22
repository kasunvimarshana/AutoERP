<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Customer\Enums;

enum CustomerAddressType: string
{
    case General = 'general';
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Registered = 'registered';
    case Service = 'service';
    case Other = 'other';
}
