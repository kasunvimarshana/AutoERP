<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Persistence;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Contracts\OAuthenticatable;
use Modules\Auth\Application\Contracts\AuthUserRepositoryInterface;
use Modules\Auth\Domain\Events\UserRegistered;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Eloquent-backed repository for auth-specific user lookups and creation.
 *
 * Lives in the Auth module's infrastructure layer so that the application
 * layer (services, strategies) depends only on AuthUserRepositoryInterface.
 */
class EloquentAuthUserRepository implements AuthUserRepositoryInterface
{
    public function __construct(
        private readonly UserModel $model,
    ) {}

    public function findForPassport(int $userId): ?OAuthenticatable
    {
        /** @var OAuthenticatable|null $user */
        $user = $this->model->find($userId);

        return $user;
    }

    public function findAuthenticatable(int $userId): ?Authenticatable
    {
        return $this->model->find($userId);
    }

    public function getEmailById(int $userId): ?string
    {
        return $this->model->find($userId)?->email;
    }

    public function getIdByEmail(string $email): ?int
    {
        $user = $this->model->where('email', $email)->first();

        return $user?->id;
    }

    public function getRolesWithPermissions(int $userId): array
    {
        $user = $this->model->with('roles.permissions')->find($userId);

        if (! $user) {
            return [];
        }

        return $user->roles->map(fn ($role) => [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ])->toArray();
    }

    public function createUser(array $data): int
    {
        /** @var UserModel $user */
        $user = $this->model->create([
            'tenant_id' => $data['tenant_id'],
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'active' => true,
        ]);

        UserRegistered::dispatch(
            $user->id,
            $user->email,
            $user->first_name,
            $user->last_name,
        );

        return $user->id;
    }
}
