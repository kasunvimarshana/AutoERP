<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Product\Application\Contracts\CategoryServiceInterface;
use Modules\Product\Domain\Entities\Category;
use Modules\Product\Domain\Events\CategoryCreated;
use Modules\Product\Domain\RepositoryInterfaces\CategoryRepositoryInterface;

class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function getCategory(string $tenantId, string $id): Category
    {
        $category = $this->categoryRepository->findById($tenantId, $id);

        if ($category === null) {
            throw new NotFoundException('Category', $id);
        }

        return $category;
    }

    public function createCategory(string $tenantId, array $data): Category
    {
        return DB::transaction(function () use ($tenantId, $data): Category {
            $now = now();
            $id = (string) Str::uuid();
            $slug = Str::slug($data['name']);

            $parentId = $data['parent_id'] ?? null;
            $level = 0;
            $path = $id;

            if ($parentId !== null) {
                $parent = $this->getCategory($tenantId, $parentId);
                $level = $parent->level + 1;
                $path = $parent->path . '/' . $id;
            }

            $category = new Category(
                id: $id,
                tenantId: $tenantId,
                parentId: $parentId,
                name: $data['name'],
                slug: $slug,
                description: $data['description'] ?? null,
                path: $path,
                level: $level,
                isActive: (bool) ($data['is_active'] ?? true),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->categoryRepository->save($category);

            Event::dispatch(new CategoryCreated($category));

            return $category;
        });
    }

    public function updateCategory(string $tenantId, string $id, array $data): Category
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Category {
            $existing = $this->getCategory($tenantId, $id);

            $updated = new Category(
                id: $existing->id,
                tenantId: $existing->tenantId,
                parentId: $existing->parentId,
                name: $data['name'] ?? $existing->name,
                slug: isset($data['name']) ? Str::slug($data['name']) : $existing->slug,
                description: $data['description'] ?? $existing->description,
                path: $existing->path,
                level: $existing->level,
                isActive: (bool) ($data['is_active'] ?? $existing->isActive),
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->categoryRepository->save($updated);

            return $updated;
        });
    }

    public function deleteCategory(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getCategory($tenantId, $id);

            $children = $this->categoryRepository->findChildren($tenantId, $id);
            if (count($children) > 0) {
                throw new \RuntimeException("Cannot delete category [{$id}] because it has children.");
            }

            $this->categoryRepository->delete($tenantId, $id);
        });
    }

    public function getAllCategories(string $tenantId): array
    {
        return $this->categoryRepository->findAll($tenantId);
    }

    public function getCategoryTree(string $tenantId): array
    {
        $all = $this->categoryRepository->findAll($tenantId);

        return $this->buildTree($all, null);
    }

    private function buildTree(array $all, ?string $parentId): array
    {
        $result = [];
        foreach ($all as $item) {
            if ($item->parentId === $parentId) {
                $children = $this->buildTree($all, $item->id);
                $result[] = ['category' => $item, 'children' => $children];
            }
        }

        return $result;
    }
}
