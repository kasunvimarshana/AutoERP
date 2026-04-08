<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\Role;

interface RoleServiceInterface
{
    public function createRole(string $tenantId, array $data): Role;

    public function updateRole(string $tenantId, string $id, array $data): Role;

    public function deleteRole(string $tenantId, string $id): void;

    public function getRole(string $tenantId, string $id): Role;

    /** @return Role[] */
    public function getAllRoles(string $tenantId): array;
}
