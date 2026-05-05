<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\CycleCountHeader;
use Modules\Inventory\Domain\Entities\CycleCountLine;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;

class EloquentCycleCountRepository implements CycleCountRepositoryInterface
{
    public function __construct(private readonly CycleCountHeaderModel $cycleCountHeaderModel) {}

    public function create(CycleCountHeader $header): CycleCountHeader
    {
        /** @var CycleCountHeaderModel $model */
        $model = $this->cycleCountHeaderModel->newQuery()->create([
            'tenant_id' => $header->getTenantId(),
            'warehouse_id' => $header->getWarehouseId(),
            'location_id' => $header->getLocationId(),
            'status' => $header->getStatus(),
            'counted_by_user_id' => $header->getCountedByUserId(),
            'counted_at' => $header->getCountedAt(),
            'approved_by_user_id' => $header->getApprovedByUserId(),
            'approved_at' => $header->getApprovedAt(),
        ]);

        foreach ($header->getLines() as $line) {
            $model->lines()->create([
                'tenant_id' => $header->getTenantId(),
                'product_id' => $line->getProductId(),
                'variant_id' => $line->getVariantId(),
                'batch_id' => $line->getBatchId(),
                'serial_id' => $line->getSerialId(),
                'system_qty' => $line->getSystemQty(),
                'counted_qty' => $line->getCountedQty(),
                'variance_qty' => $line->getVarianceQty(),
                'unit_cost' => $line->getUnitCost(),
                'variance_value' => $line->getVarianceValue(),
                'adjustment_movement_id' => $line->getAdjustmentMovementId(),
            ]);
        }

        /** @var CycleCountHeaderModel $fresh */
        $fresh = $model->fresh(['lines']);

        return $this->mapHeader($fresh);
    }

    public function findById(int $tenantId, int $countId): ?CycleCountHeader
    {
        /** @var CycleCountHeaderModel|null $model */
        $model = $this->cycleCountHeaderModel->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('id', $countId)
            ->first();

        return $model !== null ? $this->mapHeader($model) : null;
    }

    public function paginate(int $tenantId, int $perPage, int $page): mixed
    {
        return $this->cycleCountHeaderModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function markInProgress(int $tenantId, int $countId): ?CycleCountHeader
    {
        /** @var CycleCountHeaderModel|null $model */
        $model = $this->cycleCountHeaderModel->newQuery()
            ->with('lines')
            ->where('tenant_id', $tenantId)
            ->where('id', $countId)
            ->first();

        if ($model === null) {
            return null;
        }

        $model->status = 'in_progress';
        $model->save();

        /** @var CycleCountHeaderModel $fresh */
        $fresh = $model->fresh(['lines']);

        return $this->mapHeader($fresh);
    }

    public function complete(int $tenantId, int $countId, array $lineUpdates, int $approvedByUserId): ?CycleCountHeader
    {
        return DB::transaction(function () use ($tenantId, $countId, $lineUpdates, $approvedByUserId): ?CycleCountHeader {
            /** @var CycleCountHeaderModel|null $model */
            $model = $this->cycleCountHeaderModel->newQuery()
                ->with('lines')
                ->where('tenant_id', $tenantId)
                ->where('id', $countId)
                ->lockForUpdate()
                ->first();

            if ($model === null) {
                return null;
            }

            foreach ($lineUpdates as $lineUpdate) {
                $line = $model->lines->firstWhere('id', $lineUpdate['line_id']);
                if ($line === null) {
                    continue;
                }

                $countedQty = $lineUpdate['counted_qty'];
                $varianceQty = bcsub((string) $countedQty, (string) $line->system_qty, 6);
                $varianceValue = bcmul($varianceQty, (string) $line->unit_cost, 6);

                $line->counted_qty = $countedQty;
                $line->variance_qty = $varianceQty;
                $line->variance_value = $varianceValue;
                $line->adjustment_movement_id = $lineUpdate['adjustment_movement_id'];
                $line->save();
            }

            $model->status = 'completed';
            $model->approved_by_user_id = $approvedByUserId;
            $model->approved_at = now();
            $model->counted_at = now();
            $model->save();

            /** @var CycleCountHeaderModel $fresh */
            $fresh = $model->fresh(['lines']);

            return $this->mapHeader($fresh);
        });
    }

    private function mapHeader(CycleCountHeaderModel $model): CycleCountHeader
    {
        $lines = [];
        foreach ($model->lines as $line) {
            $lines[] = new CycleCountLine(
                tenantId: (int) $line->tenant_id,
                productId: (int) $line->product_id,
                variantId: $line->variant_id !== null ? (int) $line->variant_id : null,
                batchId: $line->batch_id !== null ? (int) $line->batch_id : null,
                serialId: $line->serial_id !== null ? (int) $line->serial_id : null,
                systemQty: (string) $line->system_qty,
                countedQty: (string) $line->counted_qty,
                varianceQty: (string) $line->variance_qty,
                unitCost: (string) $line->unit_cost,
                varianceValue: (string) $line->variance_value,
                adjustmentMovementId: $line->adjustment_movement_id !== null ? (int) $line->adjustment_movement_id : null,
                id: (int) $line->id,
            );
        }

        return new CycleCountHeader(
            tenantId: (int) $model->tenant_id,
            warehouseId: (int) $model->warehouse_id,
            locationId: $model->location_id !== null ? (int) $model->location_id : null,
            status: (string) $model->status,
            countedByUserId: $model->counted_by_user_id !== null ? (int) $model->counted_by_user_id : null,
            countedAt: $model->counted_at?->format('Y-m-d H:i:s'),
            approvedByUserId: $model->approved_by_user_id !== null ? (int) $model->approved_by_user_id : null,
            approvedAt: $model->approved_at?->format('Y-m-d H:i:s'),
            lines: $lines,
            id: (int) $model->id,
        );
    }
}
