<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Repositories;

use App\Shared\Abstractions\BaseRepository;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Domain\Contracts\UserRepositoryInterface;
use Modules\Auth\Domain\Entities\User;
use Modules\Auth\Infrastructure\Models\UserModel;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected function model(): string
    {
        return UserModel::class;
    }

    public function findById(int $id, int $tenantId): ?User
    {
        $model = $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByEmail(string $email, int $tenantId): ?User
    {
        $model = $this->newQuery()
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(User $user): User
    {
        if ($user->id !== null) {
            $model = $this->newQuery()->findOrFail($user->id);
        } else {
            $model = new UserModel;
        }

        $model->tenant_id = $user->tenantId;
        $model->name = $user->name;
        $model->email = $user->email;
        $model->status = $user->status;

        if ($user->passwordHash !== null) {
            $model->password = $user->passwordHash;
        }

        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id, int $tenantId): void
    {
        $this->newQuery()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail()
            ->delete();
    }

    public function verifyPassword(int $userId, int $tenantId, string $plainPassword): bool
    {
        $model = $this->newQuery()
            ->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        return $model !== null && Hash::check($plainPassword, $model->password);
    }

    public function createAuthToken(int $userId, int $tenantId, string $deviceName): string
    {
        /** @var UserModel $model */
        $model = $this->newQuery()
            ->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        return $model->createToken($deviceName)->plainTextToken;
    }

    public function revokeTokenByBearerString(int $userId, int $tenantId, string $bearerToken): void
    {
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);

        if ($token !== null && (int) $token->tokenable_id === $userId) {
            $token->delete();
        }
    }

    private function toDomain(UserModel $model): User
    {
        return new User(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            email: $model->email,
            passwordHash: null,
            status: $model->status ?? 'active',
            createdAt: $model->created_at?->toIso8601String(),
            updatedAt: $model->updated_at?->toIso8601String(),
        );
    }
}
