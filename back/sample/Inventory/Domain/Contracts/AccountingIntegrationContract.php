<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Events\InventoryMovementValuated;

interface AccountingIntegrationContract
{
    public function post(InventoryMovementValuated $event): void;
}
