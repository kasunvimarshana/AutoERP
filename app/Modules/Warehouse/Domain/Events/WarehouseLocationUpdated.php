<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Events;

use Modules\Warehouse\Domain\Entities\WarehouseLocation;

class WarehouseLocationUpdated
{
    public function __construct(public readonly WarehouseLocation $location) {}
}
