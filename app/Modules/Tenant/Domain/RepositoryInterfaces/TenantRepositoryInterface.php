<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface TenantRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a tenant by its slug.
     */
    public function findBySlug(string $slug): mixed;

    /**
     * Find a tenant by its custom domain.
     */
    public function findByDomain(string $domain): mixed;
}
