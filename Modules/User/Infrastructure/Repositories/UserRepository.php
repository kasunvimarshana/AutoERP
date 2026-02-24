<?php
namespace Modules\User\Infrastructure\Repositories;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Infrastructure\Models\UserModel;
class UserRepository implements UserRepositoryInterface
{
    public function __construct(private UserModel $model) {}
    public function findById(string $id): ?object
    {
        return $this->model->newQuery()->find($id);
    }
    public function findByEmail(string $email): ?object
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }
    public function create(array $data): object
    {
        return $this->model->newQuery()->create($data);
    }
    public function update(string $id, array $data): object
    {
        $user = $this->model->newQuery()->findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }
    public function delete(string $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }
    public function assignRole(string $userId, string $roleId): void
    {
        $user = $this->model->newQuery()->findOrFail($userId);
        $user->roles()->syncWithoutDetaching([$roleId]);
    }
    public function paginate(array $filters, int $perPage): object
    {
        $query = $this->model->newQuery();
        foreach ($filters as $key => $value) {
            if ($value !== null) {
                $query->where($key, $value);
            }
        }
        return $query->paginate($perPage);
    }
}
