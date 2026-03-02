<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Contracts;

use Modules\Auth\Domain\Entities\User;

interface UserRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?User;

    public function findByEmail(string $email, int $tenantId): ?User;

    public function save(User $user): User;

    public function delete(int $id, int $tenantId): void;

    /**
     * Verify a plain-text password against the stored hash for the given user.
     * Kept in the repository so the Application layer never touches the Eloquent model.
     */
    public function verifyPassword(int $userId, int $tenantId, string $plainPassword): bool;

    /**
     * Issue a new Sanctum API token for the given user and return the plain-text token string.
     * Kept in the repository so the Application layer never references HasApiTokens directly.
     */
    public function createAuthToken(int $userId, int $tenantId, string $deviceName): string;

    /**
     * Revoke the Sanctum access token identified by the raw Bearer token string.
     * Kept in the repository so the controller layer never calls ->delete() on an Eloquent model.
     */
    public function revokeTokenByBearerString(int $userId, int $tenantId, string $bearerToken): void;
}
