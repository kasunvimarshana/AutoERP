<?php

declare(strict_types=1);

namespace App\Application\Services\Auth;

use App\Domain\Auth\Contracts\AuthRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Application service for authentication and user management.
 *
 * Coordinates Passport SSO token issuance via the AuthRepository.
 */
final class AuthService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
    ) {}

    /**
     * Register a new user and return the created model.
     */
    public function register(array $attributes): Model
    {
        $attributes['password'] = Hash::make($attributes['password']);

        $user = $this->authRepository->create($attributes);

        // Assign default role.
        $this->authRepository->assignRole($user->id, 'staff');

        Log::info("[AuthService] User #{$user->id} registered: {$user->email}");

        return $user;
    }

    /**
     * Authenticate a user and return a Passport access token.
     *
     * @throws \App\Exceptions\AuthenticationException
     */
    public function login(string $email, string $password): array
    {
        $result = $this->authRepository->authenticate($email, $password);

        $user = $this->authRepository->findByEmail($email);

        // Record last login timestamp.
        $this->authRepository->update($user->id, ['last_login_at' => now()]);

        Log::info("[AuthService] User #{$user->id} logged in.");

        return [
            'token'      => $result,
            'token_type' => 'Bearer',
            'user'       => $user,
        ];
    }

    /**
     * Revoke all tokens for the given user (logout).
     */
    public function logout(int|string $userId): void
    {
        $this->authRepository->revokeTokens($userId);

        Log::info("[AuthService] Tokens revoked for user #{$userId}.");
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(int|string $userId, string $role): Model
    {
        return $this->authRepository->assignRole($userId, $role);
    }

    /**
     * Get the currently authenticated user.
     */
    public function me(int|string $userId): Model
    {
        return $this->authRepository->findOrFail($userId);
    }
}
