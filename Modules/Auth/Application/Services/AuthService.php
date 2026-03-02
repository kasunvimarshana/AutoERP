<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Application\DTOs\LoginDTO;
use Modules\Auth\Application\DTOs\RegisterDTO;
use Modules\Auth\Domain\Entities\User;
use Modules\Core\Domain\Contracts\ServiceContract;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Authentication service.
 *
 * Orchestrates login, logout, and token refresh use cases.
 * No business logic in controllers — this service is the single
 * authoritative point for authentication operations.
 */
class AuthService implements ServiceContract
{
    /**
     * Register a new tenant-scoped user and return a JWT token.
     *
     * The user is created inside a database transaction; the JWT token
     * is issued immediately so the caller can proceed without a second login.
     *
     * @throws AuthenticationException
     */
    public function register(RegisterDTO $dto): string
    {
        $user = DB::transaction(function () use ($dto): User {
            return User::create([
                'tenant_id' => $dto->tenantId,
                'name'      => $dto->name,
                'email'     => $dto->email,
                'password'  => $dto->password,
                'is_active' => true,
            ]);
        });

        $token = auth('api')->login($user);

        if ($token === false || $token === null) {
            throw new AuthenticationException('Could not issue token after registration.');
        }

        return $token;
    }

    /**
     * Authenticate the user and return a JWT token.
     *
     * @throws AuthenticationException
     */
    public function login(LoginDTO $dto): string
    {
        $credentials = [
            'email' => $dto->email,
            'password' => $dto->password,
        ];

        $token = auth('api')->attempt($credentials);

        if ($token === false || $token === null) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return $token;
    }

    /**
     * Invalidate the current token (logout).
     */
    public function logout(): void
    {
        auth('api')->logout();
    }

    /**
     * Refresh the current JWT token.
     *
     * @throws AuthenticationException
     */
    public function refresh(): string
    {
        $token = auth('api')->refresh();

        if ($token === null) {
            throw new AuthenticationException('Could not refresh token.');
        }

        return $token;
    }

    /**
     * Return the currently authenticated user.
     *
     * @throws AuthenticationException
     */
    public function me(): User
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $user;
    }

    /**
     * Change the password of the currently authenticated user.
     *
     * Verifies the current password before applying the change.
     * All mutations are wrapped in a database transaction.
     *
     * @throws AuthenticationException If no user is authenticated or current password is wrong.
     */
    public function changePassword(string $currentPassword, string $newPassword): void
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! Hash::check($currentPassword, $user->password)) {
            throw new AuthenticationException('Current password is incorrect.');
        }

        DB::transaction(function () use ($user, $newPassword): void {
            $user->update(['password' => $newPassword]);
        });
    }

    /**
     * Update the profile of the currently authenticated user.
     *
     * @param array<string, mixed> $data  Fields to update (name, email, etc.)
     * @throws AuthenticationException If no user is authenticated.
     */
    public function updateProfile(array $data): User
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            throw new AuthenticationException('Unauthenticated.');
        }

        DB::transaction(function () use ($user, $data): void {
            $user->update(array_filter($data, fn ($v) => $v !== null));
        });

        return $user->fresh();
    }
}
