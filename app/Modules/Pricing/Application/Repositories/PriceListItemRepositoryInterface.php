<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Repositories;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface PriceListItemRepositoryInterface extends BaseRepositoryInterface
{
    public function getForTenant(int|string $tenantId, array $with = []): Collection;

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model;

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection;

    public function paginateForOrganizationUnit(int|string $organizationUnitId, int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $context
     */
    public function findBestForContext(int|string $tenantId, int|string $priceListId, int|string $itemId, int|string $uomId, string|int|float $quantity, ?string $date = null, array $context = [], array $with = []): ?Model;
}
