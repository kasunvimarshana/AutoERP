<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Contracts\Repositories\CycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountModel;

class EloquentCycleCountRepository extends EloquentRepository implements CycleCountRepositoryInterface
{
    public function __construct(CycleCountModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find all cycle counts for a given warehouse.
     */
    public function findByWarehouse(string $warehouseId): Collection
    {
        return $this->model->newQuery()
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('counted_at')
            ->get();
    }

    /**
     * Find all cycle count lines for a given cycle count.
     */
    public function findLines(string $cycleCountId): Collection
    {
        return \Illuminate\Support\Facades\DB::table('cycle_count_lines')
            ->where('cycle_count_id', $cycleCountId)
            ->get();
    }
}
