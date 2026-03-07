<?php
namespace App\Modules\User\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements RepositoryInterface
{
    public function __construct(private User $model) {}

    public function all(array $filters = [], array $relations = [])
    {
        $query = $this->model->newQuery()->with($relations);

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (isset($filters['search'])) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('email', 'like', "%{$filters['search']}%")
            );
        }
        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query;
    }

    public function find(int $id, array $relations = []): ?User
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return $this->model->create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->find($id);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->findOrFail($id)->delete();
    }

    public function paginate(int $perPage = 15, array $filters = [], array $relations = [])
    {
        return $this->all($filters, $relations)->paginate($perPage);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }
}
