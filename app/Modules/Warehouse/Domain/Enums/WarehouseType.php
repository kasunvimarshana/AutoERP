<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

enum WarehouseType: string
{
    case Standard = 'standard';
    case Virtual = 'virtual';
    case Transit = 'transit';
    case Quarantine = 'quarantine';
}
