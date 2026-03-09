<?php

namespace App\Services;

use App\Domain\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Str;
use RuntimeException;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    public function list(string $tenantId, array $params = []): mixed
    {
        return $this->categoryRepository->list($tenantId, $params);
    }

    public function getTree(string $tenantId): array
    {
        return $this->categoryRepository->getTree($tenantId);
    }

    public function findById(string $tenantId, string $id): object
    {
        $category = $this->categoryRepository->findById($tenantId, $id);
        if (!$category) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Category not found: {$id}");
        }
        return $category;
    }

    public function create(string $tenantId, array $data): object
    {
        $data['tenant_id'] = $tenantId;

        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $existing = $this->categoryRepository->findBySlug($tenantId, $data['slug']);
        if ($existing) {
            throw new \InvalidArgumentException("A category with slug '{$data['slug']}' already exists.");
        }

        if (!empty($data['parent_id'])) {
            $parent = $this->categoryRepository->findById($tenantId, $data['parent_id']);
            if (!$parent) {
                throw new \InvalidArgumentException("Parent category not found: {$data['parent_id']}");
            }
        }

        return $this->categoryRepository->create($data);
    }

    public function update(string $tenantId, string $id, array $data): object
    {
        $category = $this->findById($tenantId, $id);

        if (isset($data['slug']) && $data['slug'] !== $category->slug) {
            $existing = $this->categoryRepository->findBySlug($tenantId, $data['slug']);
            if ($existing && $existing->id !== $id) {
                throw new \InvalidArgumentException("A category with slug '{$data['slug']}' already exists.");
            }
        }

        if (!empty($data['parent_id']) && $data['parent_id'] === $id) {
            throw new \InvalidArgumentException("A category cannot be its own parent.");
        }

        return $this->categoryRepository->update($id, $data);
    }

    public function delete(string $tenantId, string $id): bool
    {
        $this->findById($tenantId, $id);

        if ($this->categoryRepository->hasChildren($id)) {
            throw new RuntimeException("Cannot delete a category that has child categories.");
        }

        if ($this->categoryRepository->hasProducts($id)) {
            throw new RuntimeException("Cannot delete a category that has associated products.");
        }

        return $this->categoryRepository->delete($id);
    }
}
