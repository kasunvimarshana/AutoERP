<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum InventoryStockLevel: string
{
    case IN_STOCK = 'in_stock';
    case LOW_STOCK = 'low_stock';
    case OUT_OF_STOCK = 'out_of_stock';
}
