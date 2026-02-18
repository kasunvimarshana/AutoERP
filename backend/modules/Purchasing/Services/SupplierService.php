<?php

declare(strict_types=1);

namespace Modules\Purchasing\Services;

use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\Purchasing\Models\Supplier;
use Modules\Purchasing\Repositories\SupplierRepository;

/**
 * Supplier Service
 *
 * Handles business logic for supplier/vendor operations.
 */
class SupplierService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected SupplierRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all suppliers with filters.
     */
    public function list(array $filters = [])
    {
        return $this->repository->list($filters);
    }

    /**
     * Get a supplier by ID.
     */
    public function find(int $id): ?Supplier
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier
    {
        // Generate supplier code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateSupplierCode($data['name']);
        }

        // Set default status if not provided
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }

        return $this->repository->create($data);
    }

    /**
     * Update a supplier.
     */
    public function update(int $id, array $data): Supplier
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a supplier.
     */
    public function delete(int $id): bool
    {
        $supplier = $this->find($id);

        if (! $supplier) {
            throw new \Exception('Supplier not found');
        }

        // Check if supplier has purchase orders
        if ($supplier->purchaseOrders()->exists()) {
            throw new \Exception('Cannot delete supplier with existing purchase orders');
        }

        return $this->repository->delete($id);
    }

    /**
     * Activate a supplier.
     */
    public function activate(int $id): Supplier
    {
        return $this->update($id, ['status' => 'active']);
    }

    /**
     * Suspend a supplier.
     */
    public function suspend(int $id): Supplier
    {
        return $this->update($id, ['status' => 'suspended']);
    }

    /**
     * Update supplier rating.
     */
    public function updateRating(int $id, int $rating): Supplier
    {
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('Rating must be between 1 and 5');
        }

        return $this->update($id, ['rating' => $rating]);
    }

    /**
     * Generate a unique supplier code.
     */
    protected function generateSupplierCode(string $name): string
    {
        $prefix = 'SUP';
        $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
        $counter = $this->repository->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $nameCode, $counter);
    }
}
