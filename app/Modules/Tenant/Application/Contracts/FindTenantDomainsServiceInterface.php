<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tenant\Domain\Entities\TenantDomain;

interface FindTenantDomainsServiceInterface
{
    public function find(int $id): ?TenantDomain;

    public function findByDomain(string $domain): ?TenantDomain;

    public function findByTenantAndDomain(int $tenantId, string $domain): ?TenantDomain;

    public function listByTenant(int $tenantId, ?bool $isVerified = null, ?bool $isPrimary = null): Collection;

    public function paginateByTenant(int $tenantId, ?bool $isVerified, ?bool $isPrimary, int $perPage, int $page): LengthAwarePaginator;
}
