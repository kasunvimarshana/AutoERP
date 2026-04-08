<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Application\Contracts\AssignRoleServiceInterface;
use Modules\User\Domain\Events\RoleAssigned;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class AssignRoleService extends BaseService implements AssignRoleServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $repository,
        protected RoleRepositoryInterface $roleRepo
    ) {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    protected function handle(array $data): mixed
    {
        $userId = $data['user_id'];
        $roleId = $data['role_id'];

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }
        $role = $this->roleRepo->find($roleId);
        if (! $role) {
            throw new RoleNotFoundException($roleId);
        }
        if ($role->getTenantId() !== $user->getTenantId()) {
            throw new DomainException('Role does not belong to the same tenant');
        }

        $user->assignRole($role);
        $this->userRepository->syncRoles($user, $user->getRoles()->pluck('id')->toArray());
        $this->addEvent(new RoleAssigned($user, $role));

        return null;
    }
}
