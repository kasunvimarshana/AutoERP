<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\DTOs;

final class CompleteServiceOrderDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $serviceOrderId,
        /** @var array<int, array{part_name: string, part_number: string, quantity: int, unit_cost: string, inventory_item_id: ?string}> */
        public readonly array $partsUsed = [],
    ) {
    }
}
