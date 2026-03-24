<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;

class CreateRoleService extends BaseService implements CreateRoleServiceInterface
{
    private RoleRepositoryInterface $roleRepository;

    public function __construct(RoleRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->roleRepository = $repository;
    }

    protected function handle(array $data): Role
    {
        $role = new Role(
            tenantId: $data['tenant_id'],
            name: $data['name']
        );

        return $this->roleRepository->save($role);
    }
}
