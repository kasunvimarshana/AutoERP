<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Procurement\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository extends BaseRepository implements VendorRepositoryInterface
{
    /**
     * VendorRepository constructor.
     */
    public function __construct(Vendor $model)
    {
        parent::__construct($model);
    }

    /**
     * Find vendor by code
     */
    public function findByCode(string $code): mixed
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Find vendor by email
     */
    public function findByEmail(string $email): mixed
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get active vendors
     */
    public function getActiveVendors(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get vendors by type
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get vendors by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get vendors by category
     */
    public function getByCategory(string $category): Collection
    {
        return $this->model->where('vendor_category', $category)->get();
    }

    /**
     * Search vendors
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by name, code, email, phone, company
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('mobile', 'like', "%{$query}%")
                  ->orWhere('company_name', 'like', "%{$query}%")
                  ->orWhere('tax_number', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['type'])) {
            $queryBuilder->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (isset($filters['vendor_category'])) {
            $queryBuilder->where('vendor_category', $filters['vendor_category']);
        }

        if (isset($filters['assigned_to'])) {
            $queryBuilder->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['is_active'])) {
            $queryBuilder->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_verified'])) {
            $queryBuilder->where('is_verified', $filters['is_verified']);
        }

        if (isset($filters['organization_id'])) {
            $queryBuilder->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['branch_id'])) {
            $queryBuilder->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['rating_min'])) {
            $queryBuilder->where('rating', '>=', $filters['rating_min']);
        }

        if (isset($filters['rating_max'])) {
            $queryBuilder->where('rating', '<=', $filters['rating_max']);
        }

        return $queryBuilder->get();
    }
}
