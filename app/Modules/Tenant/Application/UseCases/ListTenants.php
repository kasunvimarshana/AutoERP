<?php

namespace Modules\Tenant\Application\UseCases;

use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTenants
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepo
    ) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->tenantRepo->paginate($filters, $perPage, $page);
    }
}
