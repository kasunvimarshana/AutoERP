<?php

declare(strict_types=1);

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(int $tenantId, string $email): ?User;

    public function syncRoles(User $user, array $roleIds): void;

    public function save(User $user): User;

    public function changePassword(int $userId, string $hashedPassword): void;

    public function updateAvatar(int $userId, ?string $avatarPath): void;

    public function verifyPassword(int $userId, string $plainPassword): bool;
}
