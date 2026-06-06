<?php

declare(strict_types=1);

namespace Modules\Supplier\Enums;

enum SupplierAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Registered = 'registered';
    case Warehouse = 'warehouse';
    case Other = 'other';
}
