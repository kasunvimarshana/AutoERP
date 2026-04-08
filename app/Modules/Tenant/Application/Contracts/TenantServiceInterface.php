<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Application\DTOs\TenantData;

interface TenantServiceInterface
{
    /**
     * Create a new tenant from the given DTO.
     */
    public function create(TenantData $dto): mixed;

    /**
     * Find a tenant by its unique slug.
     */
    public function findBySlug(string $slug): mixed;

    /**
     * Activate the tenant identified by $id.
     */
    public function activate(int $id): mixed;

    /**
     * Suspend the tenant identified by $id.
     */
    public function suspend(int $id): mixed;
}
