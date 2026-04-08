<?php

declare(strict_types=1);

namespace Modules\Product\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Money;
use Modules\Product\Application\DTOs\ProductData;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Events\ProductUpdated;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class UpdateProduct
{
    public function __construct(private readonly ProductRepositoryInterface $productRepo) {}

    public function execute(int $id, ProductData $data): Product
    {
        $product = $this->productRepo->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $price = new Money($data->price, $data->currency ?? 'USD');

        $product->updateDetails(
            name: $data->name,
            price: $price,
            description: $data->description,
            category: $data->category,
            attributes: $data->attributes,
            metadata: $data->metadata,
        );

        if (isset($data->status)) {
            if ($data->status === 'active') {
                $product->activate();
            } elseif ($data->status === 'inactive') {
                $product->deactivate();
            }
        }

        $saved = $this->productRepo->save($product);

        event(new ProductUpdated($saved));

        return $saved;
    }
}
