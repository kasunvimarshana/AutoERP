<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\Events\RoleAssigned;

class AssignRoleService extends BaseService
{
    public function __construct(
        UserRepositoryInterface $repository,
        protected RoleRepositoryInterface $roleRepo
    ) {
        parent::__construct($repository);
    }

    protected function handle(array $data): void
    {
        $userId = $data['user_id'];
        $roleId = $data['role_id'];

        $user = $this->repository->find($userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }
        $role = $this->roleRepo->find($roleId);
        if (!$role) {
            throw new \RuntimeException('Role not found');
        }
        if ($role->getTenantId() !== $user->getTenantId()) {
            throw new \RuntimeException('Role does not belong to the same tenant');
        }

        $user->assignRole($role);
        $this->repository->syncRoles($user, $user->getRoles()->pluck('id')->toArray());
        $this->addEvent(new RoleAssigned($user, $role));
    }
}
