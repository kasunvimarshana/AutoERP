<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\DeleteRoleServiceInterface;
use Modules\User\Domain\Exceptions\RoleNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\RoleRepositoryInterface;

class DeleteRoleService extends BaseService implements DeleteRoleServiceInterface
{
    public function __construct(private readonly RoleRepositoryInterface $roleRepository)
    {
        parent::__construct($roleRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) $data['id'];
        $role = $this->roleRepository->find($id);
        if (! $role) {
            throw new RoleNotFoundException($id);
        }

        return $this->roleRepository->delete($id);
    }
}
