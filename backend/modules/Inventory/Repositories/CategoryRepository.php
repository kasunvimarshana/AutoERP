<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\Category;

/**
 * Category Repository
 *
 * Handles data access for product categories.
 */
class CategoryRepository extends BaseRepository
{
    protected function model(): string
    {
        return Category::class;
    }

    /**
     * Get all categories with filters and pagination.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = $this->newQuery();

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        } elseif (isset($filters['root']) && $filters['root']) {
            $query->whereNull('parent_id');
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get category tree (hierarchical structure).
     */
    public function getTree(): array
    {
        return $this->newQuery()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get root categories.
     */
    public function getRootCategories(): LengthAwarePaginator
    {
        return $this->newQuery()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->paginate(15);
    }

    /**
     * Check if category code exists.
     */
    public function codeExists(string $code): bool
    {
        return $this->newQuery()->where('code', $code)->exists();
    }
}
