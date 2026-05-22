<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Enums;

enum SupplierAddressType: string
{
    case Billing = 'billing';
    case Shipping = 'shipping';
    case Office = 'office';
    case Other = 'other';
}