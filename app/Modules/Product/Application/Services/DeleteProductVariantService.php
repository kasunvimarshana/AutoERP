<?php
namespace Modules\Product\Application\Services;

use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class DeleteProductVariantService implements DeleteProductVariantServiceInterface
{
    public function __construct(private readonly ProductVariantRepositoryInterface $repository) {}

    public function execute(ProductVariant $variant): bool
    {
        return $this->repository->delete($variant);
    }
}
