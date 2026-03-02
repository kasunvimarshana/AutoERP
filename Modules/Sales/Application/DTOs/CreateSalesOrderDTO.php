<?php

declare(strict_types=1);

namespace Modules\Sales\Application\DTOs;

/**
 * Data Transfer Object for creating a SalesOrder.
 *
 * Lines array format:
 *   [
 *     ['product_id' => int, 'uom_id' => int, 'quantity' => string, 'unit_price' => string,
 *      'discount_amount' => string, 'tax_rate' => string],
 *     ...
 *   ]
 *
 * All quantity/monetary values MUST be passed as numeric strings for BCMath precision.
 */
final class CreateSalesOrderDTO
{
    /**
     * @param array<int, array{product_id: int, uom_id: int, quantity: string, unit_price: string, discount_amount: string, tax_rate: string}> $lines
     */
    public function __construct(
        public readonly int $customerId,
        public readonly string $orderDate,
        public readonly string $currencyCode,
        public readonly array $lines,
        public readonly ?string $notes,
        public readonly ?int $warehouseId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            customerId: (int) $data['customer_id'],
            orderDate: (string) $data['order_date'],
            currencyCode: (string) $data['currency_code'],
            lines: $data['lines'],
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null,
        );
    }
}
