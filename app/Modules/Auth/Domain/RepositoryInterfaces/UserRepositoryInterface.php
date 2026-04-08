<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\RepositoryInterfaces;

use Modules\Auth\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?User;

    public function findByEmail(string $tenantId, string $email): ?User;

    /** @return User[] */
    public function findAll(string $tenantId): array;

    public function save(User $user): void;

    public function updatePassword(string $tenantId, string $userId, string $hashedPassword): void;

    public function delete(string $tenantId, string $id): void;
}
