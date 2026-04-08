<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use DateTimeInterface;

class Warehouse
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $address,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
