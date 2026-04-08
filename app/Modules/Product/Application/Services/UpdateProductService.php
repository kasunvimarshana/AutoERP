<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Money;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
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
        $id = (int) $data['id'];
        $product = $this->productRepository->find($id);

        if (! $product) {
            throw new ProductNotFoundException($id);
        }

        $price = new Money(
            (float) $data['price'],
            (string) ($data['currency'] ?? 'USD')
        );

        // Only replace units of measure when the key is explicitly present in the payload.
        $unitsOfMeasure = null;
        if (array_key_exists('units_of_measure', $data) && is_array($data['units_of_measure'])) {
            $unitsOfMeasure = array_map(
                fn (array $uom) => UnitOfMeasure::fromArray($uom),
                $data['units_of_measure']
            );
        }

        // Only replace product attributes when the key is explicitly present in the payload.
        $productAttributes = null;
        if (array_key_exists('product_attributes', $data) && is_array($data['product_attributes'])) {
            $productAttributes = array_map(
                fn (array $attr) => ProductAttribute::fromArray($attr),
                $data['product_attributes']
            );
        }

        $product->updateDetails(
            name: (string) $data['name'],
            price: $price,
            description: isset($data['description']) ? (string) $data['description'] : null,
            category: isset($data['category']) ? (string) $data['category'] : null,
            attributes: (isset($data['attributes']) && is_array($data['attributes']))
                ? $data['attributes']
                : null,
            metadata: (isset($data['metadata']) && is_array($data['metadata']))
                ? $data['metadata']
                : null,
            type: array_key_exists('type', $data) ? (string) $data['type'] : null,
            unitsOfMeasure: $unitsOfMeasure,
            productAttributes: $productAttributes,
        );

        // Only mutate status when it was explicitly provided in the input payload.
        // Using array_key_exists avoids applying ProductData constructor defaults.
        if (array_key_exists('status', $data)) {
            match ((string) $data['status']) {
                'active'   => $product->activate(),
                'inactive' => $product->deactivate(),
                'draft'    => $product->draft(),
                default    => null,
            };
        }

        $saved = $this->productRepository->save($product);

        $this->addEvent(new ProductUpdated($saved));

        return $saved;
    }
}
