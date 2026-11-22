<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Supplier\Enums;

enum SupplierAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Registered = 'registered';
    case Warehouse = 'warehouse';
    case Other = 'other';
}
