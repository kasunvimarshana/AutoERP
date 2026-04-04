<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\Product;

interface DeleteProductServiceInterface
{
    public function execute(Product $product): bool;
}
