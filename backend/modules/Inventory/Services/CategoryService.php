<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\Inventory\Models\Category;
use Modules\Inventory\Repositories\CategoryRepository;

/**
 * Category Service
 *
 * Handles business logic for product category operations with hierarchical support.
 */
class CategoryService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected CategoryRepository $repository
    ) {
        parent::__construct($tenantContext);
    }

    /**
     * Get all categories with filters.
     */
    public function list(array $filters = [])
    {
        return $this->repository->list($filters);
    }

    /**
     * Get a category by ID.
     */
    public function find(int $id): ?Category
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new category.
     */
    public function create(array $data): Category
    {
        // Generate category code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateCategoryCode($data['name']);
        }

        // Set default values
        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = 0;
        }

        return $this->repository->create($data);
    }

    /**
     * Update a category.
     */
    public function update(int $id, array $data): Category
    {
        // Prevent circular parent relationships
        if (isset($data['parent_id']) && $data['parent_id']) {
            if ($data['parent_id'] === $id) {
                throw new \Exception('Category cannot be its own parent');
            }
            if ($this->isDescendantOf($id, $data['parent_id'])) {
                throw new \Exception('Cannot create circular parent relationships');
            }
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete a category.
     */
    public function delete(int $id): bool
    {
        $category = $this->find($id);

        if (! $category) {
            throw new \Exception('Category not found');
        }

        // Check if category has products
        if ($category->products()->exists()) {
            throw new \Exception('Cannot delete category with products. Please reassign or delete products first.');
        }

        // Check if category has child categories
        if ($category->hasChildren()) {
            throw new \Exception('Cannot delete category with sub-categories. Please delete or reassign sub-categories first.');
        }

        return $this->repository->delete($id);
    }

    /**
     * Get category tree (hierarchical structure).
     */
    public function getTree(): array
    {
        return $this->repository->getTree();
    }

    /**
     * Get root categories (no parent).
     */
    public function getRootCategories()
    {
        return $this->repository->getRootCategories();
    }

    /**
     * Get category children.
     */
    public function getChildren(int $id): array
    {
        $category = $this->find($id);
        if (! $category) {
            throw new \Exception('Category not found');
        }

        return $category->children()->get()->toArray();
    }

    /**
     * Activate a category.
     */
    public function activate(int $id): Category
    {
        return $this->update($id, ['is_active' => true]);
    }

    /**
     * Deactivate a category.
     */
    public function deactivate(int $id): Category
    {
        return $this->update($id, ['is_active' => false]);
    }

    /**
     * Generate a category code from name.
     */
    protected function generateCategoryCode(string $name): string
    {
        $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 10));
        $suffix = 1;
        $baseCode = $code;

        while ($this->repository->codeExists($code)) {
            $code = $baseCode.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    /**
     * Check if a category is a descendant of another.
     */
    protected function isDescendantOf(int $categoryId, int $potentialAncestorId): bool
    {
        $category = $this->find($potentialAncestorId);

        while ($category && $category->parent_id) {
            if ($category->parent_id === $categoryId) {
                return true;
            }
            $category = $this->find($category->parent_id);
        }

        return false;
    }
}
