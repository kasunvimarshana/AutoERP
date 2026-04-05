<?php
declare(strict_types=1);
namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly UserModel $model) {}

    private function toEntity(UserModel $m): User
    {
        return new User(
            $m->id,
            $m->tenant_id,
            $m->name,
            $m->email,
            $m->password,
            $m->status,
            $m->phone,
            $m->avatar,
            $m->preferences,
            $m->email_verified_at,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?User
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $m = $this->model->newQuery()->where('email', $email)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): User
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?User
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? Hash::check($password, $m->password) : false;
    }

    public function changePassword(int $id, string $hashedPassword): bool
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return false;
        }
        return (bool) $m->update(['password' => $hashedPassword]);
    }

    public function updateAvatar(int $id, ?string $avatarPath): bool
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return false;
        }
        return (bool) $m->update(['avatar' => $avatarPath]);
    }
}
