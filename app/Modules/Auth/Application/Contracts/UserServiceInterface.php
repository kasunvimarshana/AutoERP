<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\User;

interface UserServiceInterface
{
    public function createUser(string $tenantId, array $data): User;

    public function updateUser(string $tenantId, string $id, array $data): User;

    public function deleteUser(string $tenantId, string $id): void;

    public function getUser(string $tenantId, string $id): User;

    public function getUserByEmail(string $tenantId, string $email): ?User;

    /** @return User[] */
    public function getAllUsers(string $tenantId): array;
}
