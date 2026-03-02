<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Entities;

final class CycleCount
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $warehouseId,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?string $startedAt,
        public readonly ?string $completedAt,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
