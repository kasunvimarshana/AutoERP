<?php
namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class UpdateProductCategoryService implements UpdateProductCategoryServiceInterface
{
    public function __construct(private readonly ProductCategoryRepositoryInterface $repository) {}

    public function execute(ProductCategory $category, ProductCategoryData $data): ProductCategory
    {
        return $this->repository->update($category, $data->toArray());
    }
}
