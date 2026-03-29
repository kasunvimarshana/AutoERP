<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;
use Modules\Product\Domain\ValueObjects\ProductAttribute;
use Modules\Product\Domain\ValueObjects\UnitOfMeasure;

class UpdateProductService extends BaseService implements UpdateProductServiceInterface
{
    public function __construct(private readonly ProductRepositoryInterface $productRepository)
    {
        parent::__construct($productRepository);
    }

    protected function handle(array $data): Product
    {
        $id = $data['id'];
        $product = $this->productRepository->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $dto   = ProductData::fromArray($data);
        $price = new Money($dto->price, $dto->currency ?? 'USD');

        $unitsOfMeasure = null;
        if (isset($dto->units_of_measure)) {
            $unitsOfMeasure = [];
            foreach ($dto->units_of_measure as $uomData) {
                $unitsOfMeasure[] = UnitOfMeasure::fromArray($uomData);
            }
        }

        $productAttributes = null;
        if (isset($dto->product_attributes)) {
            $productAttributes = [];
            foreach ($dto->product_attributes as $attrData) {
                $productAttributes[] = ProductAttribute::fromArray($attrData);
            }
        }

        $product->updateDetails(
            name: $dto->name,
            price: $price,
            description: $dto->description,
            category: $dto->category,
            attributes: $dto->attributes,
            metadata: $dto->metadata,
            type: $dto->type ?? null,
            unitsOfMeasure: $unitsOfMeasure,
            productAttributes: $productAttributes,
        );

        if (isset($dto->status)) {
            if ($dto->status === 'active') {
                $product->activate();
            } elseif ($dto->status === 'inactive') {
                $product->deactivate();
            }
        }

        $saved = $this->productRepository->save($product);

        $this->addEvent(new ProductUpdated($saved));

        return $saved;
    }
}
