<?php

namespace Modules\User\Application\UseCases;

use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\Events\RoleAssigned;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\Exceptions\RoleNotFoundException;

class AssignRole
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private RoleRepositoryInterface $roleRepo
    ) {}

    public function execute(int $userId, int $roleId): void
    {
        $user = $this->userRepo->find($userId);
        if (!$user) {
            throw new UserNotFoundException($userId);
        }

        $role = $this->roleRepo->find($roleId);
        if (!$role) {
            throw new RoleNotFoundException($roleId);
        }

        $user->assignRole($role);
        $this->userRepo->syncRoles($user, [$roleId]);

        event(new RoleAssigned($user, $role));
    }
}
