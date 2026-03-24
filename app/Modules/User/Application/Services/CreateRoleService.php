<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;

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
