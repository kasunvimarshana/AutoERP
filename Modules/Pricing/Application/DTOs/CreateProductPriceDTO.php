<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * DTO for creating a product price entry.
 *
 * Monetary values (selling_price, cost_price) are typed as strings
 * to preserve BCMath arbitrary-precision arithmetic throughout the pipeline.
 */
class CreateProductPriceDTO extends DataTransferObject
{
    public function __construct(
        public readonly int $productId,
        public readonly int $priceListId,
        public readonly int $uomId,
        public readonly string $sellingPrice,
        public readonly ?string $costPrice,
        public readonly ?string $minQuantity,
        public readonly ?string $validFrom,
        public readonly ?string $validTo,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            productId:    (int) $data['product_id'],
            priceListId:  (int) $data['price_list_id'],
            uomId:        (int) $data['uom_id'],
            sellingPrice: (string) $data['selling_price'],
            costPrice:    isset($data['cost_price']) ? (string) $data['cost_price'] : null,
            minQuantity:  isset($data['min_quantity']) ? (string) $data['min_quantity'] : null,
            validFrom:    $data['valid_from'] ?? null,
            validTo:      $data['valid_to'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id'    => $this->productId,
            'price_list_id' => $this->priceListId,
            'uom_id'        => $this->uomId,
            'selling_price' => $this->sellingPrice,
            'cost_price'    => $this->costPrice,
            'min_quantity'  => $this->minQuantity,
            'valid_from'    => $this->validFrom,
            'valid_to'      => $this->validTo,
        ];
    }
}
