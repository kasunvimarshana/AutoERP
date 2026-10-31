<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Modules\Reporting\Http\Requests\InventoryAgingLookupRequest;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryAgingLookupController
{
    public function warehouses(InventoryAgingLookupRequest $request): JsonResponse
    {
        $query = WarehouseModel::query()
            ->forTenant($request->tenantId(), $request->organizationUnitId());

        return $this->respond($query, $request);
    }

    public function locations(InventoryAgingLookupRequest $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        $query = WarehouseLocationModel::query()
            ->forTenant($request->tenantId(), $request->organizationUnitId())
            ->where('warehouse_id', $warehouseId);

        return $this->respond($query, $request);
    }

    private function respond(Builder $query, InventoryAgingLookupRequest $request): JsonResponse
    {
        $search = trim((string) $request->validated('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        $page = $query->orderBy('name')->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => $page->getCollection()->map(static fn ($record): array => [
                'id' => (int) $record->id,
                'code' => (string) $record->code,
                'name' => (string) $record->name,
            ])->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
