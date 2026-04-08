<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Inventory\Domain\Entities\InventoryCycleCountLine;

interface InventoryCycleCountLineRepositoryInterface extends RepositoryInterface
{
    public function save(InventoryCycleCountLine $line): InventoryCycleCountLine;

    public function findByCycleCount(int $tenantId, int $cycleCountId): Collection;
}
