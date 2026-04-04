<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\ProductVariant;

interface DeleteProductVariantServiceInterface
{
    public function execute(ProductVariant $variant): bool;
}
