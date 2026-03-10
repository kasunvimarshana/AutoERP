<?php

declare(strict_types=1);

namespace App\Application\Auth\Services;

use App\Application\Auth\DTOs\LoginDTO;
use App\Application\Auth\DTOs\RegisterDTO;
use App\Domain\User\Exceptions\InvalidCredentialsException;
use App\Domain\User\Exceptions\UserAlreadyExistsException;
use App\Domain\User\Exceptions\UserInactiveException;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Infrastructure\MultiTenant\TenantManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * AuthService
 *
 * Handles user registration, login, token issuance (via Laravel Passport),
 * and token revocation.  All business logic lives here; the controller
 * remains thin.
 *
 * This service is tenant-aware: it scopes user lookups to the resolved tenant.
 */
class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TenantManager           $tenantManager,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Registration
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Register a new user for the resolved tenant.
     *
     * @param  RegisterDTO $dto
     * @return array{user: \App\Infrastructure\Persistence\Models\User, token: string}
     *
     * @throws UserAlreadyExistsException
     */
    public function register(RegisterDTO $dto): array
    {
        // Guard: no duplicate email per tenant
        $existing = $this->userRepository->findByEmailAndTenant($dto->email, $dto->tenantId);
        if ($existing !== null) {
            throw new UserAlreadyExistsException($dto->email, $dto->tenantId);
        }

        $user = $this->userRepository->create([
            'tenant_id' => $dto->tenantId,
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => Hash::make($dto->password),
            'is_active' => true,
            'metadata'  => $dto->metadata,
        ]);

        // Assign role if provided (spatie/laravel-permission)
        if ($dto->role !== null) {
            $user->assignRole($dto->role);
        } else {
            $user->assignRole('user'); // default role
        }

        $token = $user->createToken('auth_token')->accessToken;

        Log::info('User registered', [
            'user_id'   => $user->id,
            'tenant_id' => $dto->tenantId,
        ]);

        return compact('user', 'token');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Login
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Authenticate a user and issue a Passport access token.
     *
     * @param  LoginDTO $dto
     * @return array{user: \App\Infrastructure\Persistence\Models\User, token: string, expires_at: string}
     *
     * @throws InvalidCredentialsException
     * @throws UserInactiveException
     */
    public function login(LoginDTO $dto): array
    {
        $user = $this->userRepository->findByEmailAndTenant($dto->email, $dto->tenantId);

        if ($user === null || !Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$user->is_active) {
            throw new UserInactiveException($user->id);
        }

        // Revoke all existing tokens (single-session policy per tenant)
        $user->tokens()->where('revoked', false)->update(['revoked' => true]);

        $tokenResult = $user->createToken('passport_token');
        $token       = $tokenResult->accessToken;
        $expiresAt   = $tokenResult->token->expires_at?->toIso8601String()
                       ?? now()->addDays(15)->toIso8601String();

        Log::info('User logged in', [
            'user_id'   => $user->id,
            'tenant_id' => $dto->tenantId,
        ]);

        return [
            'user'       => $user,
            'token'      => $token,
            'expires_at' => $expiresAt,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Logout
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Revoke the current user's access token.
     *
     * @param  \App\Infrastructure\Persistence\Models\User $user
     * @return void
     */
    public function logout(\App\Infrastructure\Persistence\Models\User $user): void
    {
        $user->token()?->revoke();

        Log::info('User logged out', ['user_id' => $user->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Token refresh
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Revoke the current token and issue a fresh one.
     *
     * @param  \App\Infrastructure\Persistence\Models\User $user
     * @return array{token: string, expires_at: string}
     */
    public function refreshToken(\App\Infrastructure\Persistence\Models\User $user): array
    {
        $user->token()?->revoke();

        $tokenResult = $user->createToken('passport_token');

        return [
            'token'      => $tokenResult->accessToken,
            'expires_at' => $tokenResult->token->expires_at?->toIso8601String()
                            ?? now()->addDays(15)->toIso8601String(),
        ];
    }
}
