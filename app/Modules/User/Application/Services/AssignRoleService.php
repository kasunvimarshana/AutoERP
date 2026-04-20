<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\Events\RoleAssigned;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class AssignRoleService extends BaseService implements AssignRoleServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository
    ) {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): mixed
    {
        $userId = (int) $data['user_id'];
        $roleId = (int) $data['role_id'];

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
            ->map(static fn (Role $userRole): ?int => $userRole->getId())
            ->filter(static fn (?int $assignedRoleId): bool => $assignedRoleId !== null)
            ->values()
            ->toArray();
        $this->userRepository->syncRoles($user, $roleIds);
        $this->addEvent(new RoleAssigned($user, $role));

        return null;
    }
}
