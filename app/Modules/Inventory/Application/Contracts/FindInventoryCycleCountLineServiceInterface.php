<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ReadServiceInterface;

interface FindInventoryCycleCountLineServiceInterface extends ReadServiceInterface
{
    public function findByCycleCount(int $tenantId, int $cycleCountId): Collection;
}
