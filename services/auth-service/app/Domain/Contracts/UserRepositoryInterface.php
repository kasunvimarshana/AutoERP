<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\Entities\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * User Repository Interface
 *
 * Defines the contract for user data access operations.
 */
interface UserRepositoryInterface
{
    public function findById(int|string $id, array $relations = []): ?User;

    public function findByEmail(string $email): ?User;

    public function findByTenant(int|string $tenantId, array $filters = []): Collection|LengthAwarePaginator;

    public function create(array $data): User;

    public function update(int|string $id, array $data): User;

    public function delete(int|string $id): bool;

    public function exists(array $criteria): bool;

    public function findWithRoles(int|string $id): ?User;
}
