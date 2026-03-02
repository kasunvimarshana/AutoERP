<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Tenant\Application\Commands\CreateTenantCommand;
use Modules\Tenant\Application\Handlers\CreateTenantHandler;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;

/**
 * Service orchestrating all tenant management operations.
 *
 * Controllers must interact with the tenant domain exclusively through this
 * service. Read operations are fulfilled directly via the repository contract;
 * write operations are delegated to the appropriate command handlers.
 */
class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly CreateTenantHandler $createTenantHandler,
    ) {}

    /**
     * Retrieve a paginated list of tenants.
     *
     * @return array{items: Tenant[], current_page: int, last_page: int, per_page: int, total: int}
     */
    public function listTenants(int $page, int $perPage): array
    {
        return $this->tenantRepository->findAll($page, $perPage);
    }

    /**
     * Find a single tenant by its identifier.
     */
    public function findTenantById(int $tenantId): ?Tenant
    {
        return $this->tenantRepository->findById($tenantId);
    }

    /**
     * Create a new tenant and return the persisted entity.
     */
    public function createTenant(CreateTenantCommand $command): Tenant
    {
        return $this->createTenantHandler->handle($command);
    }
}
