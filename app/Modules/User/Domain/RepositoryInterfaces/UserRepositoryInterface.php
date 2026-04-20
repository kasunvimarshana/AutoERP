<?php

declare(strict_types=1);

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByTenantAndId(int $tenantId, int $userId): ?User;

    public function findByEmail(int $tenantId, string $email): ?User;

    public function syncRoles(User $user, array $roleIds): void;

    public function save(User $user): User;

    public function changePassword(int $userId, string $hashedPassword): void;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createRecord(array $attributes): int;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateRecord(int $tenantId, int $userId, array $attributes): void;

    public function updateAvatar(int $userId, ?string $avatarPath): void;

    public function verifyPassword(int $userId, string $plainPassword): bool;
}
