<?php
declare(strict_types=1);
namespace Modules\Authorization\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Authorization\Application\Contracts\PermissionServiceInterface;
use Modules\Authorization\Domain\Entities\Permission;
use Modules\Authorization\Domain\Exceptions\PermissionNotFoundException;
use Modules\Authorization\Domain\RepositoryInterfaces\PermissionRepositoryInterface;

class PermissionService implements PermissionServiceInterface
{
    public function __construct(private readonly PermissionRepositoryInterface $repo) {}

    public function findById(int $id): Permission
    {
        $permission = $this->repo->findById($id);
        if (!$permission) {
            throw new PermissionNotFoundException($id);
        }
        return $permission;
    }

    public function findAll(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repo->findAll($perPage, $page);
    }

    public function create(array $data): Permission
    {
        return $this->repo->create($data);
    }

    public function update(int $id, array $data): Permission
    {
        $permission = $this->repo->update($id, $data);
        if (!$permission) {
            throw new PermissionNotFoundException($id);
        }
        return $permission;
    }

    public function delete(int $id): bool
    {
        $permission = $this->repo->findById($id);
        if (!$permission) {
            throw new PermissionNotFoundException($id);
        }
        return $this->repo->delete($id);
    }
}
