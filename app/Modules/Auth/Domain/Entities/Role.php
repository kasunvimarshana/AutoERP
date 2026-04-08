<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use DateTimeInterface;

class Role
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $guard,
        public readonly array $permissions,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    public function grantPermission(string $permission): self
    {
        $permissions = $this->permissions;

        if (! in_array($permission, $permissions, true)) {
            $permissions[] = $permission;
        }

        return new self(
            $this->id,
            $this->tenantId,
            $this->name,
            $this->guard,
            $permissions,
            $this->createdAt,
            $this->updatedAt,
        );
    }

    public function revokePermission(string $permission): self
    {
        return new self(
            $this->id,
            $this->tenantId,
            $this->name,
            $this->guard,
            array_values(array_filter($this->permissions, fn(string $p): bool => $p !== $permission)),
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
