<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Tenant\Application\Contracts\FindTenantDomainsServiceInterface;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;

class FindTenantDomainsService implements FindTenantDomainsServiceInterface
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $tenantDomainRepository,
    ) {}

    public function find(int $id): ?TenantDomain
    {
        return $this->tenantDomainRepository->find($id);
    }

    public function findByDomain(string $domain): ?TenantDomain
    {
        return $this->tenantDomainRepository->findByDomain($domain);
    }

    public function findByTenantAndDomain(int $tenantId, string $domain): ?TenantDomain
    {
        return $this->tenantDomainRepository->findByTenantAndDomain($tenantId, $domain);
    }

    public function listByTenant(int $tenantId, ?bool $isVerified = null, ?bool $isPrimary = null): Collection
    {
        return Collection::make($this->tenantDomainRepository->getByTenant($tenantId, $isVerified, $isPrimary));
    }

    public function paginateByTenant(int $tenantId, ?bool $isVerified, ?bool $isPrimary, int $perPage, int $page): LengthAwarePaginator
    {
        $repository = $this->tenantDomainRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId);

        if ($isVerified !== null) {
            $repository->where('is_verified', $isVerified);
        }

        if ($isPrimary !== null) {
            $repository->where('is_primary', $isPrimary);
        }

        return $repository
            ->orderBy('id', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
