<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Exceptions\DomainException;

class Role
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private string $guardName;

    private ?string $description;

    private Collection $permissions;

    public function __construct(int $tenantId, string $name,
        string $guardName,
        ?string $description = null, ?int $id = null)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->guardName = $guardName;
        $this->description = $description;
        $this->permissions = new Collection;
    }

    // Getters...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGuardName(): string
    {
        return $this->guardName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function grantPermission(Permission $permission): void
    {
        if ($permission->getTenantId() !== $this->tenantId) {
            throw new DomainException('Permission does not belong to the same tenant');
        }
        if (! $this->permissions->contains('id', $permission->getId())) {
            $this->permissions->add($permission);
        }
    }

    public function revokePermission(Permission $permission): void
    {
        $this->permissions = $this->permissions->reject(fn ($p) => $p->getId() === $permission->getId());
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }
}
