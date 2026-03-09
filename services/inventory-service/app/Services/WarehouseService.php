<?php

namespace App\Services;

use App\Domain\Contracts\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {
    }

    public function list(string $tenantId, array $params = []): mixed
    {
        return $this->warehouseRepository->list($tenantId, $params);
    }

    public function findById(string $tenantId, string $id): object
    {
        $warehouse = $this->warehouseRepository->findById($tenantId, $id);
        if (!$warehouse) {
            throw new ModelNotFoundException("Warehouse not found: {$id}");
        }
        return $warehouse;
    }

    public function create(string $tenantId, array $data): object
    {
        $data['tenant_id'] = $tenantId;
        $data['code']      = strtoupper($data['code']);

        if ($this->warehouseRepository->existsByCode($tenantId, $data['code'])) {
            throw new \InvalidArgumentException("Warehouse code '{$data['code']}' already exists for this tenant.");
        }

        return $this->warehouseRepository->create($data);
    }

    public function update(string $tenantId, string $id, array $data): object
    {
        $warehouse = $this->findById($tenantId, $id);

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
            if ($this->warehouseRepository->existsByCode($tenantId, $data['code'], $id)) {
                throw new \InvalidArgumentException("Warehouse code '{$data['code']}' already exists for this tenant.");
            }
        }

        return $this->warehouseRepository->update($id, $data);
    }

    public function delete(string $tenantId, string $id): bool
    {
        $this->findById($tenantId, $id);
        return $this->warehouseRepository->delete($id);
    }

    public function getStockSummary(string $tenantId, string $warehouseId): array
    {
        $warehouse = $this->findById($tenantId, $warehouseId);
        $warehouse->load('stockLevels.product');

        $levels = $warehouse->stockLevels;

        return [
            'warehouse_id'   => $warehouseId,
            'product_count'  => $levels->count(),
            'total_available'=> (float) $levels->sum('quantity_available'),
            'total_reserved' => (float) $levels->sum('quantity_reserved'),
            'total_on_hand'  => (float) $levels->sum('quantity_on_hand'),
            'low_stock_count'=> $levels->filter(fn ($l) =>
                $l->product && $l->quantity_available <= $l->product->reorder_point
            )->count(),
        ];
    }
}
