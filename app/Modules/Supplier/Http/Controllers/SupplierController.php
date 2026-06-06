<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Http\Requests\ChangeSupplierStatusRequest;
use Modules\Supplier\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Http\Resources\SupplierCategoryResource;
use Modules\Supplier\Http\Resources\SupplierItemMappingResource;
use Modules\Supplier\Http\Resources\SupplierResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCategory;
use Modules\Supplier\Models\SupplierItemMapping;
use Modules\Supplier\Services\SupplierCreationService;
use Modules\Supplier\Services\SupplierStatusService;
use Modules\Supplier\Services\SupplierUpdateService;

final class SupplierController
{
    public function index(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $query = $this->query($request)->with(['creditProfile', 'categories']);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('supplier_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'supplier_type', 'is_credit_allowed'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn (Builder $scope): Builder => $scope->whereKey((int) $request->input('category_id')));
        }
        if ($request->filled('item_id')) {
            $query->whereHas('itemMappings', fn (Builder $scope): Builder => $scope->where('item_id', (int) $request->input('item_id'))->where('is_active', true));
        }

        return SupplierResource::collection($query
            ->orderBy((string) $request->input('sort', 'name'), (string) $request->input('direction', 'asc'))
            ->paginate($request->perPage()));
    }

    public function store(StoreSupplierRequest $request, SupplierCreationService $service): SupplierResource
    {
        return new SupplierResource($service->create($request->toData()));
    }

    public function show(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        return new SupplierResource($this->query($request)->with([
            'contacts', 'addresses', 'bankAccounts', 'categories', 'documents',
            'itemMappings', 'creditProfile', 'statusHistories',
        ])->findOrFail($supplier));
    }

    public function update(UpdateSupplierRequest $request, int $supplier, SupplierUpdateService $service): SupplierResource
    {
        $model = $this->query($request)->findOrFail($supplier);

        return new SupplierResource($service->update($model, $request->toData()));
    }

    public function changeStatus(ChangeSupplierStatusRequest $request, int $supplier, SupplierStatusService $service): SupplierResource
    {
        $model = $this->query($request)->findOrFail($supplier);

        return new SupplierResource($service->change($model, $request->toData()));
    }

    public function lookup(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $request->merge(['per_page' => min($request->perPage(), 50)]);

        return $this->index($request);
    }

    public function categories(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $query = SupplierCategory::query()->where('tenant_id', $request->tenantId());
        if ($request->organizationUnitId() !== null) {
            $query->where(fn (Builder $scope): Builder => $scope->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $request->organizationUnitId()));
        }

        return SupplierCategoryResource::collection($query->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get());
    }

    public function itemMappings(ListSupplierRequest $request): AnonymousResourceCollection
    {
        $query = SupplierItemMapping::query()->where('tenant_id', $request->tenantId())->where('is_active', true);
        if ($request->organizationUnitId() !== null) {
            $query->where(fn (Builder $scope): Builder => $scope->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $request->organizationUnitId()));
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->input('item_id'));
        }

        return SupplierItemMappingResource::collection($query->with('supplier')->paginate($request->perPage()));
    }

    private function query(ListSupplierRequest|UpdateSupplierRequest|ChangeSupplierStatusRequest $request): Builder
    {
        return Supplier::query()->forTenant($request->tenantId(), $request->organizationUnitId());
    }
}
