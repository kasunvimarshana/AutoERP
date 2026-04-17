<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\CreateRoleServiceInterface;
use Modules\User\Domain\Entities\Role;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class CreateRoleService extends BaseService implements CreateRoleServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $roleRepository)
    {
        parent::__construct($roleRepository);
    }

    protected function handle(array $data): Role
    {
        $role = new Role(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name']
        );

        return $this->roleRepository->save($role);
    }
}
