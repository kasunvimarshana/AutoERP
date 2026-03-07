<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for the Auth / User repository.
 */
interface AuthRepositoryInterface
{
    public function findOrFail(int|string $id): Model;

    public function find(int|string $id): ?Model;

    /** Find a user by email address. */
    public function findByEmail(string $email): ?Model;

    /** Create a new user record with hashed password. */
    public function create(array $attributes): Model;

    /** Update user attributes. */
    public function update(int|string $id, array $attributes): Model;

    /**
     * Attempt to authenticate a user and return a Passport personal access token string.
     *
     * @throws \App\Exceptions\AuthenticationException
     */
    public function authenticate(string $email, string $password): string;

    /**
     * Revoke all tokens for the given user (logout).
     */
    public function revokeTokens(int|string $userId): void;

    /**
     * Assign a role to a user.
     */
    public function assignRole(int|string $userId, string $role): Model;

    /**
     * Revoke a role from a user.
     */
    public function revokeRole(int|string $userId, string $role): Model;
}
