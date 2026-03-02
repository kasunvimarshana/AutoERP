<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Infrastructure\Repositories;

use DateTimeImmutable;
use Modules\Manufacturing\Domain\Contracts\ManufacturingRepositoryInterface;
use Modules\Manufacturing\Domain\Entities\Bom as BomEntity;
use Modules\Manufacturing\Domain\Entities\BomLine as BomLineEntity;
use Modules\Manufacturing\Domain\Entities\ProductionOrder as ProductionOrderEntity;
use Modules\Manufacturing\Domain\Enums\ProductionStatus;
use Modules\Manufacturing\Infrastructure\Models\Bom as BomModel;
use Modules\Manufacturing\Infrastructure\Models\ProductionOrder as ProductionOrderModel;

class ManufacturingRepository implements ManufacturingRepositoryInterface
{
    // ── BOM ───────────────────────────────────────────────────────────────

    public function createBom(array $data): BomEntity
    {
        $model = BomModel::create([
            'tenant_id'       => $data['tenant_id'],
            'product_id'      => $data['product_id'],
            'variant_id'      => $data['variant_id'] ?? null,
            'output_quantity' => $data['output_quantity'],
            'reference'       => $data['reference'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
            'created_by'      => $data['created_by'] ?? null,
        ]);

        foreach ($data['lines'] as $line) {
            $model->lines()->create([
                'component_product_id' => $line['component_product_id'],
                'component_variant_id' => $line['component_variant_id'] ?? null,
                'quantity'             => $line['quantity'],
                'notes'                => $line['notes'] ?? null,
            ]);
        }

        return $this->hydrateBom($model->load('lines'));
    }

    public function findBomById(int $id, int $tenantId): BomEntity
    {
        $model = BomModel::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with('lines')
            ->firstOrFail();

        return $this->hydrateBom($model);
    }

    public function listBoms(int $tenantId, int $page, int $perPage): array
    {
        return BomModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->with('lines')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($m) => $this->hydrateBom($m))
            ->all();
    }

    // ── Production Orders ─────────────────────────────────────────────────

    public function createProductionOrder(array $data): ProductionOrderEntity
    {
        $model = ProductionOrderModel::create($data);
        return $this->hydrateProductionOrder($model);
    }

    public function findProductionOrderById(int $id, int $tenantId): ProductionOrderEntity
    {
        $model = ProductionOrderModel::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return $this->hydrateProductionOrder($model);
    }

    public function updateProductionOrderCompletion(int $id, int $tenantId, string $producedQuantity): ProductionOrderEntity
    {
        $model = ProductionOrderModel::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $model->update([
            'status'            => 'completed',
            'produced_quantity' => $producedQuantity,
        ]);

        return $this->hydrateProductionOrder($model->fresh());
    }

    public function updateProductionOrderStatus(int $id, int $tenantId, string $status): ProductionOrderEntity
    {
        $model = ProductionOrderModel::withoutGlobalScopes()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $model->update(['status' => $status]);

        return $this->hydrateProductionOrder($model->fresh());
    }

    public function listProductionOrders(int $tenantId, int $page, int $perPage): array
    {
        return ProductionOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($m) => $this->hydrateProductionOrder($m))
            ->all();
    }

    // ── Hydration ─────────────────────────────────────────────────────────

    private function hydrateBom(BomModel $model): BomEntity
    {
        $lines = $model->lines->map(fn ($l) => new BomLineEntity(
            id: $l->id,
            bomId: $l->bom_id,
            componentProductId: $l->component_product_id,
            componentVariantId: $l->component_variant_id,
            quantity: (string) $l->quantity,
            notes: $l->notes,
        ))->all();

        return new BomEntity(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            variantId: $model->variant_id,
            outputQuantity: (string) $model->output_quantity,
            reference: $model->reference,
            isActive: (bool) $model->is_active,
            lines: $lines,
        );
    }

    private function hydrateProductionOrder(ProductionOrderModel $model): ProductionOrderEntity
    {
        return new ProductionOrderEntity(
            id: $model->id,
            tenantId: $model->tenant_id,
            referenceNo: $model->reference_no,
            productId: $model->product_id,
            variantId: $model->variant_id,
            warehouseId: $model->warehouse_id,
            bomId: $model->bom_id,
            plannedQuantity: (string) $model->planned_quantity,
            producedQuantity: (string) $model->produced_quantity,
            totalCost: (string) $model->total_cost,
            wastagePercent: (string) $model->wastage_percent,
            status: $model->status instanceof ProductionStatus
                ? $model->status
                : ProductionStatus::from((string) $model->status),
            notes: $model->notes,
            createdBy: (int) $model->created_by,
            createdAt: new DateTimeImmutable((string) $model->created_at),
        );
    }
}
