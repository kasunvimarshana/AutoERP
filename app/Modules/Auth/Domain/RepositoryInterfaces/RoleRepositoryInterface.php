<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\RepositoryInterfaces;

use Modules\Auth\Domain\Entities\Role;

interface RoleRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Role;

    public function findByName(string $tenantId, string $name): ?Role;

    /** @return Role[] */
    public function findAll(string $tenantId): array;

    public function save(Role $role): void;

    public function delete(string $tenantId, string $id): void;
}
