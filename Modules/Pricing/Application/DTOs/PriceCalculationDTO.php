<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\DTOs;

/**
 * Data Transfer Object for a price calculation request.
 *
 * All quantity values MUST be passed as numeric strings to preserve BCMath precision.
 */
final class PriceCalculationDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $quantity,
        public readonly ?int $uomId,
        public readonly ?int $customerId,
        public readonly ?int $locationId,
        public readonly ?string $customerTier,
        public readonly string $date,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (string) $data['quantity'],
            uomId: isset($data['uom_id']) ? (int) $data['uom_id'] : null,
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            locationId: isset($data['location_id']) ? (int) $data['location_id'] : null,
            customerTier: $data['customer_tier'] ?? null,
            date: (string) $data['date'],
        );
    }
}
