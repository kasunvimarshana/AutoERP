<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\Entities\ProductVariant;

interface UpdateProductVariantServiceInterface
{
    public function execute(ProductVariant $variant, ProductVariantData $data): ProductVariant;
}
