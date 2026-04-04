<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;

interface UpdateProductCategoryServiceInterface
{
    public function execute(ProductCategory $category, ProductCategoryData $data): ProductCategory;
}
