<?php

declare(strict_types=1);

namespace App\Domain\User\Repositories;

use App\Domain\Shared\BaseRepositoryInterface;
use App\Infrastructure\Persistence\Models\User;

/**
 * UserRepositoryInterface
 *
 * Extends the base contract with user-domain–specific query methods.
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a user by email scoped to a specific tenant.
     *
     * @param  string      $email
     * @param  string      $tenantId
     * @return User|null
     */
    public function findByEmailAndTenant(string $email, string $tenantId): ?User;

    /**
     * Retrieve a paginated list of users for a given tenant.
     *
     * @param  string                $tenantId
     * @param  array<string, mixed>  $filters
     * @param  int                   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listByTenant(
        string $tenantId,
        array  $filters  = [],
        int    $perPage  = 15
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
}
