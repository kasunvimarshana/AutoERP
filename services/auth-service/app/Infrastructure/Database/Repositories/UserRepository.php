<?php

declare(strict_types=1);

namespace App\Infrastructure\Database\Repositories;

use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entities\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * User Repository Implementation
 *
 * Handles data access for User entities with multi-tenant support.
 */
class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected readonly User $model
    ) {}

    public function findById(int|string $id, array $relations = []): ?User
    {
        return $this->model
            ->with($relations)
            ->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model
            ->where('email', $email)
            ->first();
    }

    public function findByTenant(int|string $tenantId, array $filters = []): Collection|LengthAwarePaginator
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('email', 'like', "%{$filters['search']}%");
                })
            )
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', $filters['is_active'])
            )
            ->when(
                isset($filters['sort_by']),
                fn ($q) => $q->orderBy(
                    $filters['sort_by'],
                    $filters['sort_dir'] ?? 'asc'
                )
            );

        // Conditional pagination: paginate when per_page is specified
        if (isset($filters['per_page'])) {
            return $query->paginate(
                (int) $filters['per_page'],
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1)
            );
        }

        return $query->get();
    }

    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    public function update(int|string $id, array $data): User
    {
        $user = $this->model->findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->model->destroy($id);
    }

    public function exists(array $criteria): bool
    {
        return $this->model->where($criteria)->exists();
    }

    public function findWithRoles(int|string $id): ?User
    {
        return $this->model
            ->with(['roles.permissions'])
            ->find($id);
    }
}
