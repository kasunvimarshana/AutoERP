<?php

namespace App\Modules\Procurement\Services;

use App\Modules\Procurement\Repositories\SupplierRepository;
use App\Services\BaseService;

/**
 * Supplier Service
 *
 * Handles business logic for supplier management.
 */
class SupplierService extends BaseService
{
    public function __construct(
        protected SupplierRepository $supplierRepository
    ) {}

    /**
     * Get all suppliers
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllSuppliers(int $perPage = 15)
    {
        return $this->supplierRepository->paginate($perPage);
    }

    /**
     * Create a new supplier
     *
     * @return \App\Modules\Procurement\Models\Supplier
     */
    public function createSupplier(array $data)
    {
        $this->logInfo('Creating new supplier', ['name' => $data['name']]);

        $supplier = $this->supplierRepository->create($data);

        $this->logInfo('Supplier created successfully', ['id' => $supplier->id]);

        return $supplier;
    }

    /**
     * Update a supplier
     *
     * @return \App\Modules\Procurement\Models\Supplier
     */
    public function updateSupplier(int $id, array $data)
    {
        $this->logInfo('Updating supplier', ['id' => $id]);

        $this->supplierRepository->update($id, $data);
        $supplier = $this->supplierRepository->findOrFail($id);

        $this->logInfo('Supplier updated successfully', ['id' => $id]);

        return $supplier;
    }

    /**
     * Delete a supplier
     */
    public function deleteSupplier(int $id): bool
    {
        $this->logInfo('Deleting supplier', ['id' => $id]);

        $result = $this->supplierRepository->delete($id);

        if ($result) {
            $this->logInfo('Supplier deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    /**
     * Get a supplier by ID
     *
     * @return \App\Modules\Procurement\Models\Supplier
     */
    public function getSupplierById(int $id)
    {
        return $this->supplierRepository->findOrFail($id);
    }

    /**
     * Get active suppliers
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveSuppliers()
    {
        return $this->supplierRepository->getActiveSuppliers();
    }

    /**
     * Search suppliers
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchSuppliers(string $search)
    {
        return $this->supplierRepository->search($search);
    }
}
