<?php

namespace App\Repositories;

use App\DTOs\UserDTO;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?User
    {
        return User::forTenant($tenantId)->find($id);
    }

    public function findByEmail(string $email, int $tenantId): ?User
    {
        return User::forTenant($tenantId)
            ->where('email', $email)
            ->first();
    }

    public function findByKeycloakId(string $keycloakId, int $tenantId): ?User
    {
        return User::forTenant($tenantId)
            ->where('keycloak_id', $keycloakId)
            ->first();
    }

    public function paginate(
        int $tenantId,
        int $perPage = 15,
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $search = null
    ): LengthAwarePaginator {
        $query = User::forTenant($tenantId);

        if ($search) {
            $query->search($search);
        }

        if (isset($filters['role'])) {
            $query->byRole($filters['role']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $allowedSortColumns = ['id', 'name', 'email', 'role', 'status', 'created_at', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSortColumns, true) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc'], true) ? $sortDir : 'desc';

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function create(UserDTO $dto): User
    {
        return User::create($dto->toArray());
    }

    public function update(int $id, int $tenantId, UserDTO $dto): User
    {
        $user = User::forTenant($tenantId)->findOrFail($id);
        $user->update($dto->toArray());

        return $user->fresh();
    }

    public function delete(int $id, int $tenantId): bool
    {
        $user = User::forTenant($tenantId)->findOrFail($id);

        return (bool) $user->delete();
    }

    public function restore(int $id, int $tenantId): bool
    {
        $user = User::withTrashed()
            ->forTenant($tenantId)
            ->findOrFail($id);

        return (bool) $user->restore();
    }

    public function getByTenant(int $tenantId): Collection
    {
        return User::forTenant($tenantId)->get();
    }

    public function countByTenant(int $tenantId): int
    {
        return User::forTenant($tenantId)->count();
    }
}
