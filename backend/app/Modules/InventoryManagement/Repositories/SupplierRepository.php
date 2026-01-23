<?php

namespace App\Modules\InventoryManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InventoryManagement\Models\Supplier;

class SupplierRepository extends BaseRepository
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }

    /**
     * Search suppliers by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['supplier_type'])) {
            $query->where('supplier_type', $criteria['supplier_type']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->orderBy('name')->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find supplier by supplier code
     */
    public function findBySupplierCode(string $supplierCode): ?Supplier
    {
        return $this->model->where('supplier_code', $supplierCode)->first();
    }

    /**
     * Find supplier by email
     */
    public function findByEmail(string $email): ?Supplier
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get active suppliers
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get suppliers by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('supplier_type', $type)->get();
    }

    /**
     * Get suppliers by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get preferred suppliers
     */
    public function getPreferred()
    {
        return $this->model->where('is_preferred', true)->where('is_active', true)->get();
    }
}
