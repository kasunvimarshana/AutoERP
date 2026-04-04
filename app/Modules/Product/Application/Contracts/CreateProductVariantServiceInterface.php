<?php
namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\Entities\ProductVariant;

interface CreateProductVariantServiceInterface
{
    public function execute(ProductVariantData $data): ProductVariant;
}
