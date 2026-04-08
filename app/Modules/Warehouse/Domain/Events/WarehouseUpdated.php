<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\Warehouse;

class WarehouseUpdated
{
    public function __construct(public readonly Warehouse $warehouse) {}
}
