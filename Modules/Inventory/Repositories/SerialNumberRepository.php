<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Enums\SerialNumberStatus;
use Modules\Inventory\Models\SerialNumber;

/**
 * SerialNumber Repository
 *
 * Handles data access operations for serial number management.
 * Provides CRUD operations and specialized queries for serial number tracking.
 */
class SerialNumberRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new SerialNumber;
    }

    /**
     * Find serial number by serial number string.
     */
    public function findBySerialNumber(string $serialNumber): ?SerialNumber
    {
        return $this->model->where('serial_number', $serialNumber)->first();
    }

    /**
     * Find serial number by serial number string or fail.
     */
    public function findBySerialNumberOrFail(string $serialNumber): SerialNumber
    {
        $serial = $this->findBySerialNumber($serialNumber);

        if (! $serial) {
            throw new \RuntimeException("Serial number {$serialNumber} not found");
        }

        return $serial;
    }

    /**
     * Get serial numbers by product.
     */
    public function getByProduct(string $productId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('product_id', $productId)
            ->with(['product', 'warehouse', 'location'])
            ->orderBy('serial_number')
            ->paginate($perPage);
    }

    /**
     * Get serial numbers by status.
     */
    public function getByStatus(SerialNumberStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->with(['product', 'warehouse', 'location'])
            ->orderBy('serial_number')
            ->paginate($perPage);
    }

    /**
     * Get available serial numbers for a product.
     */
    public function getAvailable(string $productId, ?string $warehouseId = null): array
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->where('status', SerialNumberStatus::AVAILABLE);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->pluck('serial_number', 'id')->toArray();
    }

    /**
     * Search serial numbers with filters.
     */
    public function searchSerialNumbers(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['product', 'warehouse', 'location']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('serial_number', 'like', "%{$search}%");
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        return $query->orderBy('serial_number')->paginate($perPage);
    }

    /**
     * Update and return the updated model.
     */
    public function updateAndReturn(int|string $id, array $data): SerialNumber
    {
        $serialNumber = $this->findOrFail($id);
        $serialNumber->update($data);

        return $serialNumber->fresh(['product', 'warehouse', 'location']);
    }
}
