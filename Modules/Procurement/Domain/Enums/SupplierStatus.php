<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Enums;

enum SupplierStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
