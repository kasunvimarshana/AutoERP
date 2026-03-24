<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\Entities\Tenant;

class GetTenant
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(int $id): ?Tenant
    {
        return $this->tenantRepo->find($id);
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return $this->tenantRepo->findByDomain($domain);
    }
}
