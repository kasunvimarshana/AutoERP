<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class CreateProductService extends BaseService implements CreateProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): Product
    {
        $dto = ProductData::fromArray($data);

        $sku = new Sku($dto->sku);
        $price = new Money($dto->price, $dto->currency ?? 'USD');

        $product = new Product(
            tenantId: $dto->tenant_id,
            sku: $sku,
            name: $dto->name,
            price: $price,
            description: $dto->description,
            category: $dto->category,
            status: $dto->status ?? 'active',
            attributes: $dto->attributes,
            metadata: $dto->metadata,
        );

        $saved = $this->productRepository->save($product);

        $this->addEvent(new ProductCreated($saved));

        return $saved;
    }
}
