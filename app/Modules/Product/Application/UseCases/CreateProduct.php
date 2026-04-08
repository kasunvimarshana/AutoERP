<?php

declare(strict_types=1);

namespace Modules\Product\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Money;
use Modules\Core\Domain\ValueObjects\Sku;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductCreated;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class CreateProduct
{
    public function __construct(private readonly ProductRepositoryInterface $productRepo) {}

    public function execute(ProductData $data): Product
    {
        $sku = new Sku($data->sku);
        $price = new Money($data->price, $data->currency ?? 'USD');

        $product = new Product(
            tenantId: $data->tenant_id,
            sku: $sku,
            name: $data->name,
            price: $price,
            description: $data->description,
            category: $data->category,
            status: $data->status ?? 'active',
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        $saved = $this->productRepo->save($product);

        event(new ProductCreated($saved));

        return $saved;
    }
}
