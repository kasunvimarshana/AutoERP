<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Product\Models\ProductCategory;
use Modules\Product\Repositories\ProductCategoryRepository;

/**
 * Product Category Service
 *
 * Handles business logic for product category management.
 */
class ProductCategoryService
{
    public function __construct(
        private ProductCategoryRepository $categoryRepository,
        private CodeGeneratorService $codeGenerator
    ) {}

    /**
     * Create a new category.
     */
    public function createCategory(array $data): ProductCategory
    {
        return TransactionHelper::execute(function () use ($data) {
            // Generate category code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateCategoryCode();
            }

            return $this->categoryRepository->create($data);
        });
    }

    /**
     * Update category.
     */
    public function updateCategory(string $id, array $data): ProductCategory
    {
        return TransactionHelper::execute(function () use ($id, $data) {
            return $this->categoryRepository->update($id, $data);
        });
    }

    /**
     * Delete category.
     */
    public function deleteCategory(string $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    /**
     * Generate unique category code.
     */
    private function generateCategoryCode(): string
    {
        $prefix = config('product.category.code_prefix', 'CAT-');

        return $this->codeGenerator->generate(
            $prefix,
            fn (string $code) => $this->categoryRepository->findByCode($code) !== null
        );
    }
}
