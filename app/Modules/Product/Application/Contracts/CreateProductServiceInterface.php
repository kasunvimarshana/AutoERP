<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;

interface CreateProductServiceInterface
{
    public function execute(ProductData $data): Product;
}
