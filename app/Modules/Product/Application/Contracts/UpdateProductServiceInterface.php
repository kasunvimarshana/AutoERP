<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;

interface UpdateProductServiceInterface
{
    public function execute(Product $product, ProductData $data): Product;
}
