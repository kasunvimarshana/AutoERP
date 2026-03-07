<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\DTOs\UserDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private User $model) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $sortBy = in_array($filters['sort_by'] ?? '', ['email', 'first_name', 'created_at']) ? $filters['sort_by'] : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    public function findByKeycloakId(string $keycloakId): ?User
    {
        return $this->model->where('keycloak_id', $keycloakId)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function create(UserDTO $dto): User
    {
        return $this->model->create($dto->toArray());
    }

    public function update(int $id, array $data): ?User
    {
        $user = $this->findById($id);
        if (!$user) return null;
        $user->update($data);
        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        return (bool) $user->delete();
    }
}
