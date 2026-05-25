<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Repositories;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PriceListRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name, array $with = []): ?Model;

    public function getForTenant(int|string $tenantId, array $with = []): Collection;

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model;

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection;

    public function paginateForOrganizationUnit(int|string $organizationUnitId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function getActive(array $with = []): Collection;

    public function getInactive(array $with = []): Collection;

    public function getActiveForTenantByType(int|string $tenantId, string $type, ?string $date = null, array $with = []): Collection;

    public function findDefaultForTenantByType(int|string $tenantId, string $type, ?string $date = null, array $with = []): ?Model;
}

