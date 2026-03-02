<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Commands;

final readonly class CreateSalesOrderCommand
{
    /**
     * @param  array<int, array{product_id: int, description: ?string, quantity: string, unit_price: string, tax_rate: string, discount_rate: string}>  $lines
     */
    public function __construct(
        public int $tenantId,
        public string $customerName,
        public ?string $customerEmail,
        public ?string $customerPhone,
        public string $orderDate,
        public ?string $dueDate,
        public ?string $notes,
        public string $currency,
        public array $lines,
    ) {}
}
