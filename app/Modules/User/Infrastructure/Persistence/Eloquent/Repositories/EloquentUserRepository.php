<?php
namespace Modules\User\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(UserModel $model) { parent::__construct($model); }

    public function findById(int $id): ?User
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $m = $this->model->where('email', $email)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function create(array $data): User
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(User $user, array $data): User
    {
        $m = $this->model->findOrFail($user->id);
        return $this->toEntity(parent::update($m, $data));
    }

    public function delete(User $user): bool
    {
        return parent::delete($this->model->findOrFail($user->id));
    }

    public function verifyPassword(User $user, string $password): bool
    {
        $m = $this->model->findOrFail($user->id);
        return Hash::check($password, $m->password);
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        $m = $this->model->findOrFail($user->id);
        $m->password = Hash::make($newPassword);
        return $m->save();
    }

    public function updateAvatar(User $user, string $avatarPath): User
    {
        $m = $this->model->findOrFail($user->id);
        $m->avatar = $avatarPath;
        $m->save();
        return $this->toEntity($m);
    }

    private function toEntity(object $m): User
    {
        return new User(
            id: $m->id,
            tenantId: $m->tenant_id,
            name: $m->name,
            email: $m->email,
            status: $m->status,
            avatar: $m->avatar ?? null,
            preferences: $m->preferences ?? null,
            emailVerifiedAt: $m->email_verified_at?->toDateTimeImmutable(),
        );
    }
}
