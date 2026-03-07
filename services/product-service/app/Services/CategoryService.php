<?php

namespace App\Services;

use App\DTOs\CategoryDTO;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($perPage);
    }

    public function findOrFail(int $id): Category
    {
        $category = $this->categoryRepository->findById($id);
        if (! $category) {
            abort(404, "Category #{$id} not found");
        }
        return $category;
    }

    public function create(CategoryDTO $dto): Category
    {
        $this->assertSlugUnique($dto->slug, $dto->tenantId);

        return DB::transaction(function () use ($dto): Category {
            return $this->categoryRepository->create($dto);
        });
    }

    public function update(Category $category, CategoryDTO $dto): Category
    {
        if ($dto->slug !== $category->slug) {
            $this->assertSlugUnique($dto->slug, $dto->tenantId, excludeId: $category->id);
        }

        return DB::transaction(function () use ($category, $dto): Category {
            return $this->categoryRepository->update($category, $dto);
        });
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            abort(422, 'Cannot delete a category that has associated products');
        }

        DB::transaction(function () use ($category): void {
            $this->categoryRepository->delete($category);
        });
    }

    private function assertSlugUnique(string $slug, string $tenantId, ?int $excludeId = null): void
    {
        $existing = $this->categoryRepository->findBySlug($slug, $tenantId);
        if ($existing && $existing->id !== $excludeId) {
            abort(422, "Slug '{$slug}' already exists for this tenant");
        }
    }
}
