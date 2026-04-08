<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Entities;

use DateTimeInterface;

class Supplier
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $taxNumber,
        public readonly string $currency,
        public readonly float $creditLimit,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}
}
