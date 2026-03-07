<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {}

    public function list(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->warehouseRepository->allForTenant($tenantId, $perPage);
    }

    public function findOrFail(int $id, int $tenantId): Warehouse
    {
        $warehouse = $this->warehouseRepository->findById($id, $tenantId);

        if (! $warehouse) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Warehouse [{$id}] not found for tenant [{$tenantId}]."
            );
        }

        return $warehouse;
    }

    public function create(array $data): Warehouse
    {
        if ($this->warehouseRepository->findByCode($data['code'], $data['tenant_id'])) {
            throw new \DomainException(
                "A warehouse with code [{$data['code']}] already exists for this tenant."
            );
        }

        return $this->warehouseRepository->create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        // Ensure the new code is not taken by another warehouse in the same tenant
        if (isset($data['code']) && $data['code'] !== $warehouse->code) {
            $existing = $this->warehouseRepository->findByCode($data['code'], $warehouse->tenant_id);

            if ($existing && $existing->id !== $warehouse->id) {
                throw new \DomainException(
                    "A warehouse with code [{$data['code']}] already exists for this tenant."
                );
            }
        }

        return $this->warehouseRepository->update($warehouse, $data);
    }

    public function delete(Warehouse $warehouse): bool
    {
        if ($warehouse->inventoryItems()->count() > 0) {
            throw new \DomainException(
                'Cannot delete a warehouse that has inventory items. Move or remove the items first.'
            );
        }

        return $this->warehouseRepository->delete($warehouse);
    }
}
