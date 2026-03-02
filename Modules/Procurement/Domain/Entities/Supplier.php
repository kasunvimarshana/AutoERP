<?php

declare(strict_types=1);

namespace Modules\Procurement\Domain\Entities;

final class Supplier
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $contactName,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
