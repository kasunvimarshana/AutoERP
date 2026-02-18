<?php

declare(strict_types=1);

namespace Modules\Purchasing\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Purchasing\Models\Supplier;

/**
 * Supplier Repository
 *
 * Handles data access for suppliers.
 */
class SupplierRepository extends BaseRepository
{
    protected function model(): string
    {
        return Supplier::class;
    }

    /**
     * Get all suppliers with filters and pagination.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery();

        // Apply filters
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get total count of suppliers.
     */
    public function count(): int
    {
        return $this->newQuery()->count();
    }
}
