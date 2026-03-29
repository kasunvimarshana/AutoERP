<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Product\Application\Contracts\CreateProductVariationServiceInterface;
use Modules\Product\Application\DTOs\ProductVariationData;
use Modules\Product\Domain\Entities\ProductVariation;
use Modules\Product\Domain\Events\ProductVariationCreated;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariationRepositoryInterface;

class CreateProductVariationService extends BaseService implements CreateProductVariationServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductVariationRepositoryInterface $variationRepository,
    ) {
        parent::__construct($variationRepository);
    }

    protected function handle(array $data): ProductVariation
    {
        $dto = ProductVariationData::fromArray($data);

        $product = $this->productRepository->find($dto->product_id);
        if (! $product) {
            throw new ProductNotFoundException($dto->product_id);
        }

        $sku   = new Sku($dto->sku);
        $price = new Money($dto->price, $dto->currency ?? 'USD');

        $variation = new ProductVariation(
            productId:       $dto->product_id,
            tenantId:        $dto->tenant_id,
            sku:             $sku,
            name:            $dto->name,
            price:           $price,
            attributeValues: $dto->attribute_values ?? [],
            status:          $dto->status ?? 'active',
            sortOrder:       $dto->sort_order ?? 0,
            metadata:        $dto->metadata,
        );

        $saved = $this->variationRepository->save($variation);

        $this->addEvent(new ProductVariationCreated($saved));

        return $saved;
    }
}
