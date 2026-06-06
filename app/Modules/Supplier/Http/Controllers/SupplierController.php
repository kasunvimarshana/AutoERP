<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Http\Requests\ChangeSupplierStatusRequest;
use Modules\Supplier\Http\Requests\ListSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierRequest;
use Modules\Supplier\Http\Requests\StoreSupplierWithRelationsRequest;
use Modules\Supplier\Http\Requests\UpdateSupplierRequest;
use Modules\Supplier\Http\Resources\SupplierResource;
use Modules\Supplier\Http\Resources\SupplierSummaryResource;
use Modules\Supplier\Services\SupplierCreationService;
use Modules\Supplier\Services\SupplierQueryService;
use Modules\Supplier\Services\SupplierStatusService;
use Modules\Supplier\Services\SupplierUpdateService;

final class SupplierController
{
    public function __construct(
        private readonly SupplierQueryService $queries,
        private readonly SupplierCreationService $creation,
        private readonly SupplierUpdateService $updates,
        private readonly SupplierStatusService $statuses,
    ) {}

    public function index(ListSupplierRequest $request): AnonymousResourceCollection
    {
        return SupplierSummaryResource::collection($this->queries->paginate(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
        ));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        return $this->created($this->creation->create($request->toData()));
    }

    public function storeWithRelations(StoreSupplierWithRelationsRequest $request): JsonResponse
    {
        return $this->created($this->creation->create($request->toData()));
    }

    public function show(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        return new SupplierResource($this->queries->find(
            $supplier,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));
    }

    public function update(UpdateSupplierRequest $request, int $supplier): SupplierResource
    {
        return new SupplierResource($this->updates->update(
            $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId()),
            $request->toData(),
        )->load(['defaultCurrency']));
    }

    public function destroy(ListSupplierRequest $request, int $supplier): JsonResponse
    {
        $this->queries->delete($this->queries->supplier(
            $supplier,
            $request->tenantId(),
            $request->organizationUnitId(),
        ));

        return response()->json(null, 204);
    }

    public function activate(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        return $this->changeTo($request, $supplier, SupplierStatus::Active);
    }

    public function deactivate(ListSupplierRequest $request, int $supplier): SupplierResource
    {
        return $this->changeTo($request, $supplier, SupplierStatus::Inactive);
    }

    public function changeStatus(ChangeSupplierStatusRequest $request, int $supplier): SupplierResource
    {
        $model = $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId());

        return new SupplierResource($this->statuses->change($model, $request->toData())->load('defaultCurrency'));
    }

    public function lookup(ListSupplierRequest $request, ?string $kind = null): AnonymousResourceCollection
    {
        return SupplierSummaryResource::collection($this->queries->lookup(
            $request->validated(),
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->perPage(),
            $kind ?? 'all',
        ));
    }

    private function changeTo(ListSupplierRequest $request, int $supplier, SupplierStatus $status): SupplierResource
    {
        $model = $this->queries->supplier($supplier, $request->tenantId(), $request->organizationUnitId());

        return new SupplierResource($this->statuses->changeTo(
            $model,
            $status,
            $request->currentUserId(),
        )->load('defaultCurrency'));
    }

    private function created(\Modules\Supplier\Models\Supplier $supplier): JsonResponse
    {
        return (new SupplierResource($supplier))->response()->setStatusCode(201);
    }
}
