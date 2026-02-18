<?php

namespace Modules\IAM\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\IAM\DTOs\ChangePasswordDTO;
use Modules\IAM\DTOs\UserDTO;
use Modules\IAM\Events\PasswordChanged;
use Modules\IAM\Events\RoleAssigned;
use Modules\IAM\Events\UserCreated;
use Modules\IAM\Events\UserDeleted;
use Modules\IAM\Events\UserUpdated;
use Modules\IAM\Models\User;
use Modules\IAM\Repositories\UserRepository;

class UserService extends BaseService
{
    public function __construct(
        TenantContext $tenantContext,
        protected UserRepository $userRepository
    ) {
        parent::__construct($tenantContext);
    }

    public function create(UserDTO $dto): User
    {
        $this->validateTenant();

        if ($this->userRepository->findByEmail($dto->email)) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email already exists.'],
            ]);
        }

        return $this->transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make(str()->random(16)), // Generate random password
                'tenant_id' => $dto->tenant_id ?? $this->getTenantId(),
                'avatar' => $dto->avatar,
                'phone' => $dto->phone,
                'timezone' => $dto->timezone ?? 'UTC',
                'locale' => $dto->locale ?? 'en',
                'is_active' => $dto->is_active,
            ]);

            if ($dto->roles) {
                $user->syncRoles($dto->roles);
            }

            if ($dto->permissions) {
                $user->syncPermissions($dto->permissions);
            }

            $this->dispatchEvent(new UserCreated($user));

            return $user->load('roles', 'permissions');
        });
    }

    public function update(int $id, UserDTO $dto): User
    {
        $this->validateTenant();

        $user = $this->userRepository->findOrFail($id);

        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        if ($dto->email !== $user->email && $this->userRepository->findByEmail($dto->email)) {
            throw ValidationException::withMessages([
                'email' => ['A user with this email already exists.'],
            ]);
        }

        return $this->transaction(function () use ($user, $dto) {
            $changes = [];
            $attributes = [
                'name' => $dto->name,
                'email' => $dto->email,
                'avatar' => $dto->avatar,
                'phone' => $dto->phone,
                'timezone' => $dto->timezone,
                'locale' => $dto->locale,
                'is_active' => $dto->is_active,
            ];

            foreach ($attributes as $key => $value) {
                if ($value !== null && $user->{$key} !== $value) {
                    $changes[$key] = ['old' => $user->{$key}, 'new' => $value];
                }
            }

            $this->userRepository->update($user, array_filter($attributes, fn ($v) => $v !== null));

            if ($dto->roles !== null) {
                $user->syncRoles($dto->roles);
                $changes['roles'] = $dto->roles;
            }

            if ($dto->permissions !== null) {
                $user->syncPermissions($dto->permissions);
                $changes['permissions'] = $dto->permissions;
            }

            $user->refresh();
            $this->dispatchEvent(new UserUpdated($user, $changes));

            return $user->load('roles', 'permissions');
        });
    }

    public function delete(int $id): void
    {
        $this->validateTenant();

        $user = $this->userRepository->findOrFail($id);

        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $this->transaction(function () use ($user) {
            $email = $user->email;
            $userId = $user->id;

            $this->userRepository->delete($user);
            $this->dispatchEvent(new UserDeleted($userId, $email));
        });
    }

    public function find(int $id): ?User
    {
        $user = $this->userRepository->find($id);

        if ($user && $user->tenant_id !== $this->getTenantId()) {
            return null;
        }

        return $user?->load('roles', 'permissions');
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        $this->validateTenant();

        return $this->userRepository->getUsersWithRoles($this->getTenantId(), $perPage);
    }

    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        $this->validateTenant();

        return $this->userRepository->searchUsers($this->getTenantId(), $query, $perPage);
    }

    public function changePassword(User $user, ChangePasswordDTO $dto): void
    {
        if (! Hash::check($dto->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        if ($dto->new_password !== $dto->new_password_confirmation) {
            throw ValidationException::withMessages([
                'new_password' => ['The password confirmation does not match.'],
            ]);
        }

        $this->transaction(function () use ($user, $dto) {
            $this->userRepository->update($user, [
                'password' => Hash::make($dto->new_password),
            ]);

            // Revoke all tokens except current
            $currentToken = $user->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();

            $this->dispatchEvent(new PasswordChanged($user));
        });
    }

    public function updateProfile(User $user, array $data): User
    {
        $allowedFields = ['name', 'avatar', 'phone', 'timezone', 'locale'];
        $filtered = array_intersect_key($data, array_flip($allowedFields));

        $this->userRepository->update($user, $filtered);
        $user->refresh();

        $this->dispatchEvent(new UserUpdated($user, $filtered));

        return $user;
    }

    public function assignRole(User $user, string $roleName): void
    {
        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $this->transaction(function () use ($user, $roleName) {
            $user->assignRole($roleName);
            $role = $user->roles()->where('name', $roleName)->first();

            if ($role) {
                $this->dispatchEvent(new RoleAssigned($user, $role));
            }
        });
    }

    public function removeRole(User $user, string $roleName): void
    {
        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $user->removeRole($roleName);
    }

    public function syncRoles(User $user, array $roleNames): void
    {
        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $user->syncRoles($roleNames);
    }

    public function givePermission(User $user, string $permissionName): void
    {
        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $user->givePermissionTo($permissionName);
    }

    public function revokePermission(User $user, string $permissionName): void
    {
        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $user->revokePermissionTo($permissionName);
    }

    public function syncPermissions(User $user, array $permissionNames): void
    {
        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $user->syncPermissions($permissionNames);
    }

    public function activate(int $id): User
    {
        $this->validateTenant();

        $user = $this->userRepository->findOrFail($id);

        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $this->userRepository->update($user, ['is_active' => true]);
        $user->refresh();

        $this->dispatchEvent(new UserUpdated($user, ['is_active' => true]));

        return $user;
    }

    public function deactivate(int $id): User
    {
        $this->validateTenant();

        $user = $this->userRepository->findOrFail($id);

        if ($user->tenant_id !== $this->getTenantId()) {
            throw new \RuntimeException('User does not belong to current tenant');
        }

        $this->userRepository->update($user, ['is_active' => false]);
        $user->tokens()->delete(); // Revoke all tokens
        $user->refresh();

        $this->dispatchEvent(new UserUpdated($user, ['is_active' => false]));

        return $user;
    }

    public function enableMfa(User $user): string
    {
        $secret = $this->generateMfaSecret();
        $user->enableMfa($secret);

        return $secret;
    }

    public function disableMfa(User $user): void
    {
        $user->disableMfa();
    }

    private function generateMfaSecret(): string
    {
        return base64_encode(random_bytes(32));
    }
}
