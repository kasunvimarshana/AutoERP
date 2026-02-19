<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Product\Repositories\ProductCategoryRepository;

/**
 * Product Category Service
 *
 * Contains business logic for ProductCategory operations
 */
class ProductCategoryService extends BaseService
{
    /**
     * ProductCategoryService constructor
     */
    public function __construct(ProductCategoryRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new category
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        // Validate code uniqueness
        if (isset($data['code']) && $this->repository->codeExists($data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The category code has already been taken.'],
            ]);
        }

        // Validate parent exists if provided
        if (isset($data['parent_id']) && $data['parent_id']) {
            $parent = $this->repository->find($data['parent_id']);
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => ['The selected parent category does not exist.'],
                ]);
            }
        }

        return parent::create($data);
    }

    /**
     * Update category
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        // Validate code uniqueness
        if (isset($data['code']) && $this->repository->codeExists($data['code'], $id)) {
            throw ValidationException::withMessages([
                'code' => ['The category code has already been taken.'],
            ]);
        }

        // Validate parent exists and prevent circular reference
        if (isset($data['parent_id']) && $data['parent_id']) {
            if ($data['parent_id'] == $id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['A category cannot be its own parent.'],
                ]);
            }

            $parent = $this->repository->find($data['parent_id']);
            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_id' => ['The selected parent category does not exist.'],
                ]);
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Get category tree
     */
    public function getCategoryTree(): mixed
    {
        return $this->repository->getCategoryTree();
    }

    /**
     * Get root categories
     */
    public function getRootCategories(): mixed
    {
        return $this->repository->getRootCategories();
    }

    /**
     * Get child categories
     */
    public function getChildren(int $parentId): mixed
    {
        return $this->repository->getChildren($parentId);
    }

    /**
     * Get category with products
     */
    public function getWithProducts(int $id): mixed
    {
        return $this->repository->findWithProducts($id);
    }

    /**
     * Search categories
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }

    /**
     * Get active categories
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }
}
