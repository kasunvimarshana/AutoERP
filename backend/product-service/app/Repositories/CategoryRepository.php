<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function findById(int $id): ?Category
    {
        return Category::with(['parent', 'children'])->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh(['parent', 'children']);
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = Category::with(['parent'])
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('description', 'like', "%{$filters['search']}%")
            )
            )
            ->when(isset($filters['parent_id']), fn (Builder $q) => $q->where('parent_id', $filters['parent_id'])
            )
            ->when(isset($filters['is_active']), fn (Builder $q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy($filters['sort_by'] ?? 'name', $filters['sort_dir'] ?? 'asc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
