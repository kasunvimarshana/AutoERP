<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Enums;

enum WarehouseStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
