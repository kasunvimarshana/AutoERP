<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Application\Contracts\SyncRolePermissionsServiceInterface;

class SyncRolePermissionsService extends BaseService implements SyncRolePermissionsServiceInterface
{
    private RoleRepositoryInterface $roleRepository;

    public function __construct(RoleRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->roleRepository = $repository;
    }

    protected function handle(array $data): mixed
    {
        $roleId = $data['role_id'];
        $permissionIds = $data['permission_ids'] ?? [];

        $role = $this->roleRepository->find($roleId);
        if (!$role) {
            throw new RoleNotFoundException($roleId);
        }

        $this->roleRepository->syncPermissions($role, $permissionIds);

        return $this->roleRepository->find($roleId);
    }
}
