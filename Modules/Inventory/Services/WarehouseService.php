<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Entities\Warehouse;
use Modules\Inventory\Repositories\Contracts\WarehouseRepositoryInterface;

/**
 * Warehouse Service
 *
 * Handles business logic for warehouse management operations.
 */
class WarehouseService extends BaseService
{
    /**
     * WarehouseService constructor.
     */
    public function __construct(
        protected WarehouseRepositoryInterface $warehouseRepository
    ) {}

    /**
     * Get paginated warehouses.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->warehouseRepository->paginate($perPage);
    }

    /**
     * Create a new warehouse.
     */
    public function create(array $data): Warehouse
    {
        return $this->executeInTransaction(function () use ($data) {
            if (empty($data['code'])) {
                $data['code'] = $this->generateWarehouseCode();
            }

            $warehouse = $this->warehouseRepository->create($data);

            $this->logInfo('Warehouse created', [
                'warehouse_id' => $warehouse->id,
                'code' => $warehouse->code,
            ]);

            return $warehouse;
        });
    }

    /**
     * Update an existing warehouse.
     */
    public function update(int $id, array $data): Warehouse
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $warehouse = $this->warehouseRepository->update($id, $data);

            $this->logInfo('Warehouse updated', [
                'warehouse_id' => $warehouse->id,
            ]);

            return $warehouse;
        });
    }

    /**
     * Delete a warehouse.
     */
    public function delete(int $id): bool
    {
        $result = $this->warehouseRepository->delete($id);

        if ($result) {
            $this->logInfo('Warehouse deleted', [
                'warehouse_id' => $id,
            ]);
        }

        return $result;
    }

    /**
     * Find warehouse by ID.
     */
    public function findById(int $id): ?Warehouse
    {
        return $this->warehouseRepository->findById($id);
    }

    /**
     * Find warehouse by code.
     */
    public function findByCode(string $code): ?Warehouse
    {
        return $this->warehouseRepository->findByCode($code);
    }

    /**
     * Get active warehouses.
     */
    public function getActiveWarehouses(): Collection
    {
        return $this->warehouseRepository->getActiveWarehouses();
    }

    /**
     * Search warehouses.
     */
    public function search(string $query): Collection
    {
        return $this->warehouseRepository->search($query);
    }

    /**
     * Generate a unique warehouse code.
     */
    protected function generateWarehouseCode(): string
    {
        $prefix = 'WH';

        $lastWarehouse = $this->warehouseRepository
            ->getModel()
            ->where('code', 'LIKE', "{$prefix}-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastWarehouse) {
            $parts = explode('-', $lastWarehouse->code);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%03d', $prefix, $sequence);
    }
}
