<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

/**
 * Delegates tenant read queries to the repository.
 *
 * Keeping query logic here (rather than in the controller) upholds DIP:
 * controllers depend on this service interface, not on the repository directly.
 */
class FindTenantService implements FindTenantServiceInterface
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    public function find(int $id): ?Tenant
    {
        return $this->tenantRepository->find($id);
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return $this->tenantRepository->findByDomain($domain);
    }
}
