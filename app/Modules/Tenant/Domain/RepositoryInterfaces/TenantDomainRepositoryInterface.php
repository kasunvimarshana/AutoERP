<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Tenant\Domain\Entities\TenantDomain;

interface TenantDomainRepositoryInterface extends RepositoryInterface
{
    public function findByDomain(string $domain): ?TenantDomain;

    public function findByTenantAndDomain(int $tenantId, string $domain): ?TenantDomain;

    /**
     * @return iterable<int, TenantDomain>
     */
    public function getByTenant(int $tenantId, ?bool $isVerified = null, ?bool $isPrimary = null): iterable;

    public function save(TenantDomain $tenantDomain): TenantDomain;
}
