<?php

declare(strict_types=1);

namespace Modules\POS\Domain\Entities;

use DateTimeInterface;

class Terminal
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $warehouseId,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function activate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            code: $this->code,
            warehouseId: $this->warehouseId,
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            code: $this->code,
            warehouseId: $this->warehouseId,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
