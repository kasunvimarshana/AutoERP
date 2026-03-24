<?php

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;

class DeleteRoleService extends BaseService implements DeleteRoleServiceInterface
{
    private RoleRepositoryInterface $roleRepository;

    public function __construct(RoleRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->roleRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $role = $this->roleRepository->find($id);
        if (!$role) {
            throw new RoleNotFoundException($id);
        }

        return $this->roleRepository->delete($id);
    }
}
