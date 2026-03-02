<?php

declare(strict_types=1);

namespace Modules\Wms\Domain\Entities;

final class Bin
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly int $aisleId,
        public readonly string $code,
        public readonly ?string $description,
        public readonly ?int $maxCapacity,
        public readonly int $currentCapacity,
        public readonly bool $isActive,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
