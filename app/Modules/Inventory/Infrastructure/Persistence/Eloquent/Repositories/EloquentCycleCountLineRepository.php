<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Inventory\Domain\Entities\CycleCountLine;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountLineRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountLineModel;

class EloquentCycleCountLineRepository implements CycleCountLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?CycleCountLine
    {
        $model = CycleCountLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByCycleCount(string $tenantId, string $cycleCountId): array
    {
        return CycleCountLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('cycle_count_id', $cycleCountId)
            ->get()
            ->map(fn(CycleCountLineModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(CycleCountLine $line): void
    {
        /** @var CycleCountLineModel $model */
        $model = CycleCountLineModel::withoutGlobalScopes()->findOrNew($line->id);
        $model->fill([
            'tenant_id'      => $line->tenantId,
            'cycle_count_id' => $line->cycleCountId,
            'product_id'     => $line->productId,
            'variant_id'     => $line->variantId,
            'system_qty'     => $line->systemQty,
            'counted_qty'    => $line->countedQty,
            'variance'       => $line->variance,
            'batch_number'   => $line->batchNumber,
            'lot_number'     => $line->lotNumber,
            'serial_number'  => $line->serialNumber,
        ]);
        if (!$model->exists) {
            $model->id = $line->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        CycleCountLineModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(CycleCountLineModel $model): CycleCountLine
    {
        return new CycleCountLine(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            cycleCountId: (string) $model->cycle_count_id,
            productId: (string) $model->product_id,
            variantId: $model->variant_id !== null ? (string) $model->variant_id : null,
            systemQty: (float) $model->system_qty,
            countedQty: $model->counted_qty !== null ? (float) $model->counted_qty : null,
            variance: $model->variance !== null ? (float) $model->variance : null,
            batchNumber: $model->batch_number !== null ? (string) $model->batch_number : null,
            lotNumber: $model->lot_number !== null ? (string) $model->lot_number : null,
            serialNumber: $model->serial_number !== null ? (string) $model->serial_number : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
