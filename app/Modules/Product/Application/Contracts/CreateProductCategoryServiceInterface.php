<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductCategoryData;
use Modules\Product\Domain\Entities\ProductCategory;

interface CreateProductCategoryServiceInterface
{
    public function execute(ProductCategoryData $data): ProductCategory;
}
