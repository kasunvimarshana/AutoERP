<?php
declare(strict_types=1);
namespace Modules\Authorization\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Application\Contracts\RoleServiceInterface;
use Modules\Authorization\Domain\Entities\Role;
use Modules\Authorization\Domain\Exceptions\RoleNotFoundException;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class RoleService implements RoleServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $repo) {}

    public function findById(int $id): Role
    {
        $role = $this->repo->findById($id);
        if (!$role) {
            throw new RoleNotFoundException($id);
        }
        return $role;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findByTenant($tenantId, $perPage, $page);
    }

    public function create(array $data): Role
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): Role
    {
        $role = $this->repo->update($id, $data);
        if (!$role) {
            throw new RoleNotFoundException($id);
        }
        return $role;
    }

    public function delete(int $id): bool
    {
        $role = $this->repo->findById($id);
        if (!$role) {
            throw new RoleNotFoundException($id);
        }
        return $this->repo->delete($id);
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->repo->syncPermissions($roleId, $permissionIds);
    }

    public function getPermissions(int $roleId): array
    {
        return $this->repo->getPermissions($roleId);
    }
}
