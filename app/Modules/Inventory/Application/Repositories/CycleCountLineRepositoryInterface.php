<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Repositories;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface CycleCountLineRepositoryInterface extends BaseRepositoryInterface
{
    public function getForTenant(int|string $tenantId, array $with = []): Collection;

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model;

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection;

    public function paginateForOrganizationUnit(int|string $organizationUnitId, int $perPage = 15, array $with = []): LengthAwarePaginator;
}
