<?php

namespace App\Repositories\Interfaces;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;

interface CategoryRepositoryInterface
{
    public function queryBuilder(): QueryBuilder;

    public function findById(int $id): ?Category;

    public function findBySlug(string $slug, string $tenantId): ?Category;

    public function create(CategoryDTO $dto): Category;

    public function update(Category $category, CategoryDTO $dto): Category;

    public function delete(Category $category): bool;

    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
