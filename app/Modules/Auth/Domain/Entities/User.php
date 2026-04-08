<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use DateTimeInterface;

class User
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $email,
        public readonly string $name,
        public readonly string $role,
        public readonly string $status,
        public readonly array $preferences,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
