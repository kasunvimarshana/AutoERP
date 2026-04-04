<?php
namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class UpdateProductVariantService implements UpdateProductVariantServiceInterface
{
    public function __construct(private readonly ProductVariantRepositoryInterface $repository) {}

    public function execute(ProductVariant $variant, ProductVariantData $data): ProductVariant
    {
        return $this->repository->update($variant, $data->toArray());
    }
}
