<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a permission by its slug.
     */
    public function findBySlug(string $slug): mixed;

    /**
     * Find a permission by its UUID.
     */
    public function findByUuid(string $uuid): mixed;

    /**
     * Get all permissions belonging to a specific module.
     */
    public function findByModule(string $module): mixed;
}
