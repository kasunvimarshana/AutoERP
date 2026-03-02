<?php

declare(strict_types=1);

namespace Modules\Wms\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Facades\DB;
use Modules\Wms\Domain\Contracts\CycleCountRepositoryInterface;
use Modules\Wms\Domain\Entities\CycleCount;
use Modules\Wms\Domain\Entities\CycleCountLine;
use Modules\Wms\Infrastructure\Models\CycleCountLineModel;
use Modules\Wms\Infrastructure\Models\CycleCountModel;

class CycleCountRepository extends BaseRepository implements CycleCountRepositoryInterface
{
    protected function model(): string
    {
        return CycleCountModel::class;
    }

    public function findById(int $id, int $tenantId): ?CycleCount
    {
        $model = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findAll(int $tenantId, int $warehouseId, int $page, int $perPage): array
    {
        $paginator = $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()
                ->map(fn (CycleCountModel $m) => $this->toDomain($m))
                ->all(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ];
    }

    public function save(CycleCount $cycleCount): CycleCount
    {
        if ($cycleCount->id !== null) {
            $model = $this->newQuery()
                ->where('tenant_id', $cycleCount->tenantId)
                ->findOrFail($cycleCount->id);
        } else {
            $model = new CycleCountModel;
            $model->tenant_id = $cycleCount->tenantId;
            $model->warehouse_id = $cycleCount->warehouseId;
        }

        $model->status = $cycleCount->status;
        $model->notes = $cycleCount->notes;
        $model->started_at = $cycleCount->startedAt;
        $model->completed_at = $cycleCount->completedAt;
        $model->save();

        return $this->toDomain($model);
    }

    public function saveLines(int $cycleCountId, int $tenantId, array $lines): array
    {
        return DB::transaction(function () use ($cycleCountId, $tenantId, $lines): array {
            $saved = [];

            foreach ($lines as $lineData) {
                $model = new CycleCountLineModel;
                $model->cycle_count_id = $cycleCountId;
                $model->tenant_id = $tenantId;
                $model->product_id = $lineData['product_id'];
                $model->bin_id = $lineData['bin_id'] ?? null;
                $model->system_qty = (string) $lineData['system_qty'];
                $model->counted_qty = (string) $lineData['counted_qty'];
                $model->variance = (string) $lineData['variance'];
                $model->notes = $lineData['notes'] ?? null;
                $model->save();

                $saved[] = $this->toLineDomain($model);
            }

            return $saved;
        });
    }

    public function findLines(int $cycleCountId, int $tenantId): array
    {
        return CycleCountLineModel::where('cycle_count_id', $cycleCountId)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get()
            ->map(fn (CycleCountLineModel $m) => $this->toLineDomain($m))
            ->all();
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->findOrFail($id)
            ->delete();
    }

    private function toDomain(CycleCountModel $model): CycleCount
    {
        return new CycleCount(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            status: $model->status,
            notes: $model->notes,
            startedAt: $model->started_at?->toIso8601String(),
            completedAt: $model->completed_at?->toIso8601String(),
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }

    private function toLineDomain(CycleCountLineModel $model): CycleCountLine
    {
        return new CycleCountLine(
            id: $model->id,
            cycleCountId: $model->cycle_count_id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            binId: $model->bin_id !== null ? (int) $model->bin_id : null,
            systemQty: bcadd((string) $model->system_qty, '0', 4),
            countedQty: bcadd((string) $model->counted_qty, '0', 4),
            variance: bcadd((string) $model->variance, '0', 4),
            notes: $model->notes,
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
