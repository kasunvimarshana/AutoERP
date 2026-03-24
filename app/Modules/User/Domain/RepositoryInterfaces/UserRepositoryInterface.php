<?php

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(int $tenantId, string $email): ?User;
    public function syncRoles(User $user, array $roleIds): void;
    public function save(User $user): User;
}
