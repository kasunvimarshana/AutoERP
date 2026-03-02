<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

/**
 * Data Transfer Object for adding a UOM conversion factor to a product.
 *
 * Stores the conversion factor as a BCMath-safe string to avoid floating-point drift.
 * Per AGENT.md: no implicit UOM conversions — explicit product-specific factors only.
 */
final class AddUomConversionDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly int $fromUomId,
        public readonly int $toUomId,
        public readonly string $factor,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId:  (int) $data['product_id'],
            fromUomId:  (int) $data['from_uom_id'],
            toUomId:    (int) $data['to_uom_id'],
            factor:     (string) $data['factor'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id'  => $this->productId,
            'from_uom_id' => $this->fromUomId,
            'to_uom_id'   => $this->toUomId,
            'factor'      => $this->factor,
        ];
    }
}
