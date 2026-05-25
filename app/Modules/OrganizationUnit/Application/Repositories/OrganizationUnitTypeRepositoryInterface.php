<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\Repositories;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface OrganizationUnitTypeRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name, array $with = []): ?Model;

    public function getForTenant(int|string $tenantId, array $with = []): Collection;

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model;

    public function getActive(array $with = []): Collection;

    public function getInactive(array $with = []): Collection;
}

