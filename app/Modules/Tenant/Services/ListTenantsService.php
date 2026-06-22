<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Results\Result;
use Modules\Tenant\Repositories\TenantRepositoryInterface;

final class ListTenantsService
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}
    /** @param array<string, mixed> $filters */
    public function execute(array $filters): Result
    {
        return Result::success($this->tenants->pageByFilters(
            isset($filters['status']) ? (string) $filters['status'] : null,
            isset($filters['search']) ? (string) $filters['search'] : null,
            min(max((int) ($filters['per_page'] ?? 20), 1), 100),
            max((int) ($filters['page'] ?? 1), 1),
        ));
    }
}
