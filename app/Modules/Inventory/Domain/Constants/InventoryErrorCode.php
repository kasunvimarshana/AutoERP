<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Constants;

final class InventoryErrorCode
{
    public const INVALID_VALUE = 'INVENTORY_INVALID_VALUE';
    public const NOT_FOUND = 'INVENTORY_NOT_FOUND';
    public const INVALID_STRATEGY = 'INVENTORY_INVALID_STRATEGY';
    public const INSUFFICIENT_STOCK = 'INVENTORY_INSUFFICIENT_STOCK';
}
