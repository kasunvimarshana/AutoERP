<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Domain\User\Models\User;

/**
 * User Repository Interface
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a user by email within a tenant.
     */
    public function findByEmail(string $email, string $tenantId): ?User;

    /**
     * Find a user by their external SSO identifier.
     */
    public function findBySsoId(string $ssoId, string $provider): ?User;
}
