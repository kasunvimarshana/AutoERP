<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Enums;

enum WarehouseLocationType: string
{
    case Zone = 'zone';
    case Aisle = 'aisle';
    case Rack = 'rack';
    case Shelf = 'shelf';
    case Bin = 'bin';
    case Staging = 'staging';
    case Dispatch = 'dispatch';
}
