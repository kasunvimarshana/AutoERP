<?php
namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\DTOs\ProductVariantData;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\Events\ProductVariantCreated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class CreateProductVariantService implements CreateProductVariantServiceInterface
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function execute(ProductVariantData $data): ProductVariant
    {
        $product = $this->productRepository->findById($data->productId);
        if ($product === null) {
            throw new \InvalidArgumentException("Product with ID {$data->productId} not found.");
        }
        $variant = $this->variantRepository->create($data->toArray());
        Event::dispatch(new ProductVariantCreated($product->tenantId, $variant->id));
        return $variant;
    }
}
