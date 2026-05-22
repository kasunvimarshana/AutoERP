<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Services\Accounting;

use Modules\Inventory\Domain\Contracts\AccountingIntegrationContract;
use Modules\Inventory\Domain\Events\InventoryMovementValuated;

class NullAccountingIntegration implements AccountingIntegrationContract
{
    public function post(InventoryMovementValuated $event): void
    {
        // No-op default integration.
    }
}
