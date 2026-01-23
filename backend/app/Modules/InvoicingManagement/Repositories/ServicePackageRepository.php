<?php

namespace App\Modules\InvoicingManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InvoicingManagement\Models\ServicePackage;

class ServicePackageRepository extends BaseRepository
{
    public function __construct(ServicePackage $model)
    {
        parent::__construct($model);
    }

    /**
     * Search service packages by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('package_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['package_type'])) {
            $query->where('package_type', $criteria['package_type']);
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
     * Find service package by package code
     */
    public function findByPackageCode(string $packageCode): ?ServicePackage
    {
        return $this->model->where('package_code', $packageCode)->first();
    }

    /**
     * Get active service packages
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get service packages by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('package_type', $type)->get();
    }

    /**
     * Get popular service packages
     */
    public function getPopular(int $limit = 10)
    {
        return $this->model->where('is_active', true)
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
