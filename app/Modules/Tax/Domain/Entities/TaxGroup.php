<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Entities;

use DateTimeInterface;

class TaxGroup
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly bool $isCompound,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isCompound(): bool
    {
        return $this->isCompound;
    }
}
