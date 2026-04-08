<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): mixed;

    /**
     * Find a user by their UUID.
     */
    public function findByUuid(string $uuid): mixed;

    /**
     * Find a user along with their roles and permissions eagerly loaded.
     */
    public function findWithRoles(int $id): mixed;

    /**
     * Find a user scoped by tenant.
     */
    public function findByTenant(int $userId, int $tenantId): mixed;
}
