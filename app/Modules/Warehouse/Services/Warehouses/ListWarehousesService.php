<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Core\DTOs\PagedResult;
use Modules\Warehouse\Constants\WarehouseDefaults;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseModel;
use Throwable;

final class ListWarehousesService
{
    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $tenantId = (int) $criteria['tenant_id'];
            $organizationUnitId = $criteria['organization_unit_id'] ?? null;
            $resolvedPage = $page > 0 ? $page : WarehouseDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('warehouse.pagination.max_per_page', WarehouseDefaults::MAX_PER_PAGE))
                : (int) config('warehouse.pagination.default_per_page', WarehouseDefaults::DEFAULT_PER_PAGE);

            $query = WarehouseModel::query()
                ->forTenant($tenantId, is_numeric($organizationUnitId) ? (int) $organizationUnitId : null)
                ->with(['organizationUnit', 'defaultLocation'])
                ->withCount('locations');

            $this->applyFilters($query, $criteria);

            $paginator = $query
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->orderBy('name')
                ->paginate($resolvedPerPage, ['*'], 'page', $resolvedPage);

            return Result::success(new PagedResult(
                $paginator->items(),
                $paginator->total(),
                $paginator->currentPage(),
                $paginator->perPage(),
            ));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouses could not be listed.'));
        }
    }

    private function applyFilters(Builder $query, array $criteria): void
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        foreach (['type', 'is_active', 'is_default'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }

        if (! empty($criteria['organization_unit_filter_id'])) {
            $query->where('organization_unit_id', (int) $criteria['organization_unit_filter_id']);
        }
    }
}
