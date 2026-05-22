<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Enums;

enum SupplierStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';
}
