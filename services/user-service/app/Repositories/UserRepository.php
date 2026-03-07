<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    private const ALLOWED_SORT_COLUMNS = [
        'id', 'email', 'first_name', 'last_name', 'username',
        'department', 'is_active', 'last_login_at', 'created_at', 'updated_at',
    ];

    public function __construct(private readonly User $model) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['is_active'])) {
            $active = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($active !== null) {
                $query->where('is_active', $active);
            }
        }

        if (! empty($filters['department'])) {
            $query->byDepartment($filters['department']);
        }

        if (! empty($filters['role'])) {
            $query->hasRole($filters['role']);
        }

        $sortBy        = $this->sanitiseSortColumn($filters['sort_by'] ?? 'created_at');
        $sortDirection = $this->sanitiseSortDirection($filters['sort_direction'] ?? 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByKeycloakId(string $keycloakId): ?User
    {
        return $this->model->newQuery()->where('keycloak_id', $keycloakId)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int $id, array $data): ?User
    {
        $user = $this->findById($id);

        if ($user === null) {
            return null;
        }

        $user->update($data);

        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);

        if ($user === null) {
            return false;
        }

        return (bool) $user->delete();
    }

    public function search(string $term, int $limit = 15): Collection
    {
        return $this->model->newQuery()
            ->search($term)
            ->limit($limit)
            ->get();
    }

    public function upsertByKeycloakId(string $keycloakId, array $data): User
    {
        $user = $this->findByKeycloakId($keycloakId);

        if ($user === null) {
            return $this->create(array_merge($data, ['keycloak_id' => $keycloakId]));
        }

        $user->update($data);

        return $user->fresh();
    }

    private function sanitiseSortColumn(string $column): string
    {
        return in_array($column, self::ALLOWED_SORT_COLUMNS, true) ? $column : 'created_at';
    }

    private function sanitiseSortDirection(string $direction): string
    {
        return in_array(strtolower($direction), ['asc', 'desc'], true) ? strtolower($direction) : 'desc';
    }
}
