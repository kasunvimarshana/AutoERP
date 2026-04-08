<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Models\UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?User
    {
        $model = UserModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByEmail(string $tenantId, string $email): ?User
    {
        $model = UserModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return UserModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(UserModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(User $user): void
    {
        /** @var UserModel $model */
        $model = UserModel::withoutGlobalScopes()->findOrNew($user->id);

        $model->fill([
            'tenant_id'   => $user->tenantId,
            'email'       => $user->email,
            'name'        => $user->name,
            'role'        => $user->role,
            'status'      => $user->status,
            'preferences' => $user->preferences,
        ]);

        if (! $model->exists) {
            $model->id = $user->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        UserModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    public function updatePassword(string $tenantId, string $userId, string $hashedPassword): void
    {
        UserModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('id', $userId)
            ->update(['password' => $hashedPassword]);
    }

    private function mapToEntity(UserModel $model): User
    {
        return new User(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            email: (string) $model->email,
            name: (string) $model->name,
            role: (string) $model->role,
            status: (string) $model->status,
            preferences: (array) ($model->preferences ?? []),
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
