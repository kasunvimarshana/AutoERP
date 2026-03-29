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
use Modules\Product\Domain\ValueObjects\ProductAttribute;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;

class CreateProductService extends BaseService implements CreateProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): Product
    {
        $dto = ProductData::fromArray($data);

        $sku   = new Sku($dto->sku);
        $price = new Money($dto->price, $dto->currency ?? 'USD');

        $unitsOfMeasure = [];
        foreach ($dto->units_of_measure ?? [] as $uomData) {
            $unitsOfMeasure[] = UnitOfMeasure::fromArray($uomData);
        }

        $productAttributes = [];
        foreach ($dto->product_attributes ?? [] as $attrData) {
            $productAttributes[] = ProductAttribute::fromArray($attrData);
        }

        $product = new Product(
            tenantId: $dto->tenant_id,
            sku: $sku,
            name: $dto->name,
            price: $price,
            description: $dto->description,
            category: $dto->category,
            status: $dto->status ?? 'active',
            type: $dto->type ?? 'physical',
            unitsOfMeasure: $unitsOfMeasure,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
            productAttributes: $productAttributes,
        );

        $saved = $this->productRepository->save($product);

        $this->addEvent(new ProductCreated($saved));

        return $saved;
    }
}
