<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Commands;

final readonly class CreatePurchaseOrderCommand
{
    /**
     * @param  array<int, array{product_id: int, description: ?string, quantity: float|string, unit_cost: float|string, tax_rate: ?float|?string, discount_rate: ?float|?string}>  $lines
     */
    public function __construct(
        public int $tenantId,
        public int $supplierId,
        public string $orderDate,
        public ?string $expectedDeliveryDate,
        public ?string $notes,
        public string $currency,
        public array $lines,
    ) {}
}
