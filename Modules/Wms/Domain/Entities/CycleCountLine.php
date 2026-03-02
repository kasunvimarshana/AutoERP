<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Entities;

final class CycleCountLine
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $cycleCountId,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly ?int $binId,
        public readonly string $systemQty,
        public readonly string $countedQty,
        public readonly string $variance,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
