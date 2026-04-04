<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\ProductCategory;

interface DeleteProductCategoryServiceInterface
{
    public function execute(ProductCategory $category): bool;
}
