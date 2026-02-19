<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Enums\WarehouseStatus;
use Modules\Inventory\Exceptions\WarehouseNotFoundException;
use Modules\Inventory\Models\Warehouse;

/**
 * Warehouse Repository
 *
 * Handles data access operations for warehouse management.
 * Provides CRUD operations and specialized queries for warehouse records.
 */
class WarehouseRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new Warehouse;
    }

    /**
     * Find warehouse by code.
     *
     * @param  string  $code  Warehouse code
     */
    public function findByCode(string $code): ?Warehouse
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Find warehouse by code or fail.
     *
     * @param  string  $code  Warehouse code
     *
     * @throws WarehouseNotFoundException
     */
    public function findByCodeOrFail(string $code): Warehouse
    {
        $warehouse = $this->findByCode($code);

        if (! $warehouse) {
            throw new WarehouseNotFoundException("Warehouse with code {$code} not found");
        }

        return $warehouse;
    }

    /**
     * Get warehouses by status.
     *
     * @param  WarehouseStatus  $status  Warehouse status
     * @param  int  $perPage  Results per page
     */
    public function getByStatus(WarehouseStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get active warehouses.
     *
     * @param  int  $perPage  Results per page
     */
    public function getActive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(WarehouseStatus::ACTIVE, $perPage);
    }

    /**
     * Get inactive warehouses.
     *
     * @param  int  $perPage  Results per page
     */
    public function getInactive(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(WarehouseStatus::INACTIVE, $perPage);
    }

    /**
     * Get warehouses by location.
     *
     * @param  array  $location  Location criteria (city, state, country)
     * @param  int  $perPage  Results per page
     */
    public function getByLocation(array $location, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($location['city'])) {
            $query->where('city', $location['city']);
        }

        if (! empty($location['state'])) {
            $query->where('state', $location['state']);
        }

        if (! empty($location['country'])) {
            $query->where('country', $location['country']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get default warehouse.
     */
    public function getDefault(): ?Warehouse
    {
        return $this->model
            ->where('is_default', true)
            ->where('status', WarehouseStatus::ACTIVE)
            ->first();
    }

    /**
     * Search warehouses with filters.
     *
     * @param  array  $filters  Search filters
     * @param  int  $perPage  Results per page
     *
     * @throws \InvalidArgumentException if tenant_id is not provided
     */
    public function searchWarehouses(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // Enforce tenant isolation - tenant_id is required
        if (empty($filters['tenant_id'])) {
            throw new \InvalidArgumentException('tenant_id is required for warehouse queries to maintain tenant isolation');
        }
        
        $query = $this->model->query()->with(['organization']);

        $query->where('tenant_id', $filters['tenant_id']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
        }

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Activate warehouse.
     *
     * @param  int|string  $id  Warehouse ID
     */
    public function activate(int|string $id): bool
    {
        return $this->update($id, ['status' => WarehouseStatus::ACTIVE]);
    }

    /**
     * Deactivate warehouse.
     *
     * @param  int|string  $id  Warehouse ID
     */
    public function deactivate(int|string $id): bool
    {
        return $this->update($id, ['status' => WarehouseStatus::INACTIVE]);
    }

    /**
     * Set warehouse as default.
     *
     * @param  int|string  $id  Warehouse ID
     */
    public function setAsDefault(int|string $id): bool
    {
        $this->beginTransaction();

        try {
            // Remove default flag from all warehouses
            $this->bulkUpdate(
                ['is_default' => true],
                ['is_default' => false]
            );

            // Set the specified warehouse as default
            $result = $this->update($id, ['is_default' => true]);

            $this->commit();

            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Update warehouse and return the updated model.
     *
     * @param  int|string  $id  Warehouse ID
     * @param  array  $data  Data to update
     */
    public function updateAndReturn(int|string $id, array $data): Warehouse
    {
        $warehouse = $this->findOrFail($id);
        $warehouse->update($data);

        return $warehouse->fresh();
    }
}
