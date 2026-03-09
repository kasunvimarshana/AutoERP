<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Contracts\AuthServiceInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entities\Role;
use App\Domain\Entities\User;
use App\Domain\Events\UserLoggedIn;
use App\Domain\Events\UserRegistered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Auth Service Implementation
 *
 * Handles authentication, token management, and authorization checks.
 * Uses Laravel Passport for OAuth2 token issuance.
 */
class AuthService implements AuthServiceInterface
{
    public function __construct(
        protected readonly UserRepositoryInterface $userRepository
    ) {}

    public function register(array $data): array
    {
        // Check for duplicate email within tenant
        if ($this->userRepository->exists([
            'email' => $data['email'],
            'tenant_id' => $data['tenant_id'],
        ])) {
            throw ValidationException::withMessages([
                'email' => ['Email already registered for this tenant.'],
            ]);
        }

        $user = $this->userRepository->create([
            'tenant_id' => $data['tenant_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // Will be hashed via cast
            'is_active' => true,
        ]);

        // Assign default role if specified
        if (isset($data['role'])) {
            $this->assignRole($user->id, $data['role']);
        }

        Event::dispatch(new UserRegistered($user, $data['tenant_id']));

        $token = $user->createToken('auth-token')->accessToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Account is deactivated.'],
            ]);
        }

        // Validate tenant context
        if (isset($credentials['tenant_id']) && $user->tenant_id != $credentials['tenant_id']) {
            throw ValidationException::withMessages([
                'email' => ['User does not belong to this tenant.'],
            ]);
        }

        // Revoke previous tokens for this device if requested
        if ($credentials['revoke_previous'] ?? false) {
            $user->tokens()->delete();
        }

        $tokenName = $credentials['device_name'] ?? 'auth-token';
        $token = $user->createToken($tokenName)->accessToken;

        Event::dispatch(new UserLoggedIn(
            $user,
            request()->ip() ?? '',
            request()->userAgent() ?? ''
        ));

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(int|string $userId): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return false;
        }

        $user->tokens()->delete();

        return true;
    }

    public function refreshToken(string $token): array
    {
        // Passport handles token refresh via the oauth/token endpoint
        // This method is provided for manual refresh scenarios
        $user = Auth::guard('api')->user();

        if (!$user) {
            throw ValidationException::withMessages([
                'token' => ['Invalid or expired token.'],
            ]);
        }

        // Revoke current token and issue new one
        $user->token()->revoke();
        $newToken = $user->createToken('auth-token')->accessToken;

        return [
            'access_token' => $newToken,
            'token_type' => 'Bearer',
        ];
    }

    public function validateToken(string $token): ?array
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return null;
            }

            return [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => $user->roles->pluck('name')->toArray(),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function assignRole(int|string $userId, string $role): bool
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return false;
        }

        $roleModel = Role::firstOrCreate(
            ['name' => $role, 'tenant_id' => $user->tenant_id],
            ['description' => "Role: {$role}"]
        );

        $user->roles()->syncWithoutDetaching([$roleModel->id]);

        return true;
    }

    public function hasPermission(int|string $userId, string $permission): bool
    {
        $user = $this->userRepository->findWithRoles($userId);

        if (!$user) {
            return false;
        }

        return $user->hasPermission($permission);
    }
}
