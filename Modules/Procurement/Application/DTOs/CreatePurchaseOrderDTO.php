<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\DTOs;

/**
 * Data Transfer Object for creating a PurchaseOrder.
 *
 * Lines array format:
 *   [
 *     ['product_id' => int, 'uom_id' => int, 'quantity' => string, 'unit_cost' => string],
 *     ...
 *   ]
 *
 * All quantity/monetary values MUST be passed as numeric strings for BCMath precision.
 */
final class CreatePurchaseOrderDTO
{
    /**
     * @param array<int, array{product_id: int, uom_id: int, quantity: string, unit_cost: string}> $lines
     */
    public function __construct(
        public readonly int $vendorId,
        public readonly string $orderDate,
        public readonly ?string $expectedDeliveryDate,
        public readonly string $currencyCode,
        public readonly array $lines,
        public readonly ?string $notes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            vendorId: (int) $data['vendor_id'],
            orderDate: (string) $data['order_date'],
            expectedDeliveryDate: isset($data['expected_delivery_date']) ? (string) $data['expected_delivery_date'] : null,
            currencyCode: (string) $data['currency_code'],
            lines: $data['lines'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }
}
