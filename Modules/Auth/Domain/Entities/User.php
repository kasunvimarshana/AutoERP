<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

final class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $passwordHash,
        public readonly string $status,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}
}
