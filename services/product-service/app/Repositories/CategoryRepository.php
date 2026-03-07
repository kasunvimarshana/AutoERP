<?php

namespace App\Repositories;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function queryBuilder(): QueryBuilder
    {
        return QueryBuilder::for(Category::class)
            ->allowedFilters([
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('slug'),
            ])
            ->allowedSorts(['name', 'sort_order', 'created_at'])
            ->allowedIncludes(['products', 'children', 'parent'])
            ->defaultSort('sort_order');
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    public function findBySlug(string $slug, string $tenantId): ?Category
    {
        return Category::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function create(CategoryDTO $dto): Category
    {
        return Category::create($dto->toArray());
    }

    public function update(Category $category, CategoryDTO $dto): Category
    {
        $category->update($dto->toArray());
        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryBuilder()->paginate($perPage)->appends(request()->query());
    }
}
