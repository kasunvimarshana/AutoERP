<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\RepositoryInterfaces\CostLayerRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCostLayerModel;

class EloquentCostLayerRepository implements CostLayerRepositoryInterface
{
    public function __construct(private readonly InventoryCostLayerModel $model) {}

    public function create(InventoryCostLayer $layer): InventoryCostLayer
    {
        /** @var InventoryCostLayerModel $saved */
        $saved = $this->model->newQuery()->create($this->toArray($layer));

        return $this->mapToEntity($saved);
    }

    public function update(InventoryCostLayer $layer): InventoryCostLayer
    {
        $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('id', $layer->getId())
            ->update([
                'quantity_remaining' => $layer->getQuantityRemaining(),
                'unit_cost' => $layer->getUnitCost(),
                'is_closed' => $layer->isClosed(),
                'updated_at' => now(),
            ]);

        return $layer;
    }

    public function findOpenLayersOldestFirst(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array {
        return $this->fetchOpenLayers($tenantId, $productId, $locationId, $variantId, 'layer_date', 'asc');
    }

    public function findOpenLayersNewestFirst(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array {
        return $this->fetchOpenLayers($tenantId, $productId, $locationId, $variantId, 'layer_date', 'desc');
    }

    public function findOpenLayersByExpiryAsc(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array {
        $rows = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('inventory_cost_layers.tenant_id', $tenantId)
            ->where('inventory_cost_layers.product_id', $productId)
            ->where('inventory_cost_layers.location_id', $locationId)
            ->where('inventory_cost_layers.is_closed', false)
            ->where(fn ($q) => $q->where('inventory_cost_layers.quantity_remaining', '>', 0))
            ->when(
                $variantId !== null,
                fn ($q) => $q->where('inventory_cost_layers.variant_id', $variantId),
                fn ($q) => $q->whereNull('inventory_cost_layers.variant_id'),
            )
            ->leftJoin('batches', 'batches.id', '=', 'inventory_cost_layers.batch_id')
            ->select('inventory_cost_layers.*')
            ->orderByRaw('CASE WHEN batches.expiry_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('batches.expiry_date', 'asc')
            ->orderBy('inventory_cost_layers.layer_date', 'asc')
            ->get();

        return $rows->map(fn (InventoryCostLayerModel $m) => $this->mapToEntity($m))->all();
    }

    public function findAllOpenLayers(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array {
        return $this->fetchOpenLayers($tenantId, $productId, $locationId, $variantId, 'id', 'asc');
    }

    public function findById(int $tenantId, int $id): ?InventoryCostLayer
    {
        /** @var InventoryCostLayerModel|null $model */
        $model = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function fetchOpenLayers(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId,
        string $orderColumn,
        string $orderDirection,
    ): array {
        $rows = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('is_closed', false)
            ->where('quantity_remaining', '>', 0)
            ->when(
                $variantId !== null,
                fn ($q) => $q->where('variant_id', $variantId),
                fn ($q) => $q->whereNull('variant_id'),
            )
            ->orderBy($orderColumn, $orderDirection)
            ->orderBy('id', $orderDirection)
            ->get();

        return $rows->map(fn (InventoryCostLayerModel $m) => $this->mapToEntity($m))->all();
    }

    private function toArray(InventoryCostLayer $layer): array
    {
        return [
            'tenant_id' => $layer->getTenantId(),
            'product_id' => $layer->getProductId(),
            'variant_id' => $layer->getVariantId(),
            'batch_id' => $layer->getBatchId(),
            'location_id' => $layer->getLocationId(),
            'valuation_method' => $layer->getValuationMethod(),
            'layer_date' => $layer->getLayerDate(),
            'quantity_in' => $layer->getQuantityIn(),
            'quantity_remaining' => $layer->getQuantityRemaining(),
            'unit_cost' => $layer->getUnitCost(),
            'reference_type' => $layer->getReferenceType(),
            'reference_id' => $layer->getReferenceId(),
            'is_closed' => $layer->isClosed(),
        ];
    }

    private function mapToEntity(InventoryCostLayerModel $model): InventoryCostLayer
    {
        return new InventoryCostLayer(
            tenantId: (int) $model->tenant_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            batchId: $model->batch_id !== null ? (int) $model->batch_id : null,
            locationId: (int) $model->location_id,
            valuationMethod: (string) $model->valuation_method,
            layerDate: (string) $model->layer_date,
            quantityIn: bcadd((string) $model->quantity_in, '0', 6),
            quantityRemaining: bcadd((string) $model->quantity_remaining, '0', 6),
            unitCost: bcadd((string) $model->unit_cost, '0', 6),
            referenceType: $model->reference_type,
            referenceId: $model->reference_id !== null ? (int) $model->reference_id : null,
            isClosed: (bool) $model->is_closed,
            id: (int) $model->id,
        );
    }
}
