<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface RoleRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a role by its slug, optionally scoped to a tenant.
     */
    public function findBySlug(string $slug, ?int $tenantId = null): mixed;

    /**
     * Find a role by its UUID.
     */
    public function findByUuid(string $uuid): mixed;

    /**
     * Find a role with its permissions eagerly loaded.
     */
    public function findWithPermissions(int $id): mixed;

    /**
     * Find a role with its users eagerly loaded (for cache invalidation).
     */
    public function findWithUsers(int $id): mixed;
}
