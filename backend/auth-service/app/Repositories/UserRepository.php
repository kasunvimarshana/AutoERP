<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email, ?int $tenantId = null): ?User
    {
        $query = User::withoutGlobalScopes()->where('email', $email);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function paginate(array $filters = [], ?int $perPage = null): LengthAwarePaginator|Collection
    {
        $query = User::query()
            ->when(isset($filters['search']), fn (Builder $q) => $q->where(fn (Builder $inner) => $inner->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")
            )
            )
            ->when(isset($filters['role']), fn (Builder $q) => $q->whereJsonContains('roles', $filters['role'])
            )
            ->orderBy($filters['sort_by'] ?? 'created_at', $filters['sort_dir'] ?? 'desc');

        return $perPage !== null ? $query->paginate($perPage) : $query->get();
    }
}
