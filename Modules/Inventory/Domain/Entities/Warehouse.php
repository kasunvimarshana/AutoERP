<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

final class Warehouse
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $address,
        public readonly string $status,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
