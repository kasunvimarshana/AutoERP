<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Application\Commands;

/**
 * Command to create a new Production Order.
 */
final class CreateProductionOrderCommand
{
    public function __construct(
        public readonly int     $tenantId,
        public readonly int     $productId,
        public readonly ?int    $variantId,
        public readonly int     $warehouseId,
        public readonly int     $bomId,
        public readonly string  $plannedQuantity,
        public readonly string  $wastagePercent,
        public readonly ?string $notes,
        public readonly int     $createdBy,
    ) {}
}
