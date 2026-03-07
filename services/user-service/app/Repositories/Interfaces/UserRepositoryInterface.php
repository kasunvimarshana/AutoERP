<?php

namespace App\Repositories\Interfaces;

use App\DTOs\UserDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?User;

    public function findByEmail(string $email, int $tenantId): ?User;

    public function findByKeycloakId(string $keycloakId, int $tenantId): ?User;

    /**
     * @return LengthAwarePaginator<User>
     */
    public function paginate(
        int $tenantId,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $search = null
    ): LengthAwarePaginator;

    public function create(UserDTO $dto): User;

    public function update(int $id, int $tenantId, UserDTO $dto): User;

    public function delete(int $id, int $tenantId): bool;

    public function restore(int $id, int $tenantId): bool;

    /**
     * @return Collection<int, User>
     */
    public function getByTenant(int $tenantId): Collection;

    public function countByTenant(int $tenantId): int;
}
