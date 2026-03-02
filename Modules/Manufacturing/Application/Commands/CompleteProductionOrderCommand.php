<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Application\Commands;

/**
 * Command to mark a production order as completed and record stock movements.
 */
final class CompleteProductionOrderCommand
{
    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $orderId,
        public readonly string $producedQuantity,
        public readonly int    $completedBy,
    ) {}
}
