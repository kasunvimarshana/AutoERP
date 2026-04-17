<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Events\RoleAssigned;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class AssignRole
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository
    ) {}

    public function execute(int $userId, int $roleId): void
    {
        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $role = $this->roleRepository->find($roleId);
        if (! $role) {
            throw new RoleNotFoundException($roleId);
        }

        if ($role->getTenantId() !== $user->getTenantId()) {
            throw new DomainException('Role does not belong to the same tenant');
        }

        $user->assignRole($role);
        $roleIds = $user->getRoles()
            ->map(static fn (Role $assignedRole): ?int => $assignedRole->getId())
            ->filter(static fn (?int $assignedRoleId): bool => $assignedRoleId !== null)
            ->values()
            ->toArray();
        $this->userRepository->syncRoles($user, $roleIds);

        event(new RoleAssigned($user, $role));
    }
}
