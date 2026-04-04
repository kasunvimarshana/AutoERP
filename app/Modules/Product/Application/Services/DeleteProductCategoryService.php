<?php
namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class DeleteProductCategoryService implements DeleteProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $repository) {}

    public function execute(ProductCategory $category): bool
    {
        return $this->repository->delete($category);
    }
}
