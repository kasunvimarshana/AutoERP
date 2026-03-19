<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email, ?string $tenantId = null): ?User;

    public function findByTenantId(string $tenantId): Collection;

    public function create(array $data): User;

    public function update(int $id, array $data): User;

    public function delete(int $id): bool;

    public function updateLastLogin(int $userId, string $ipAddress): void;

    public function incrementTokenVersion(int $userId): void;
}
