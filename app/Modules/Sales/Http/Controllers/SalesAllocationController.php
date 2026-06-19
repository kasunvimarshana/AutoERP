<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesAllocationRequest;
use Modules\Sales\Http\Resources\SalesAllocationResource;
use Modules\Sales\Models\SalesAllocation;
use Modules\Sales\Services\SalesAllocationService;
use Modules\Sales\Services\SalesAuthorizationService;

final class SalesAllocationController
{
    use FiltersSalesQueries;
    use ScopesSalesRequests;

    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::ALLOCATIONS_VIEW);

        $query = $this->scope(SalesAllocation::query(), $request)->with($this->relations());
        $this->applySalesFilters($query, $request, 'allocation_number', 'allocation_date');

        return SalesAllocationResource::collection($query->latest('allocation_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesAllocationRequest $request, SalesAllocationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::ALLOCATIONS_CREATE);

        return (new SalesAllocationResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $allocation): SalesAllocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::ALLOCATIONS_VIEW);

        return new SalesAllocationResource($this->scope(SalesAllocation::query(), $request)->with($this->relations())->findOrFail($allocation));
    }

    public function release(SalesActionRequest $request, int $allocation, SalesAllocationService $service): SalesAllocationResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::ALLOCATIONS_RELEASE);

        return new SalesAllocationResource($service->release($this->scope(SalesAllocation::query(), $request)->findOrFail($allocation), $request->currentUserId()));
    }

    private function relations(): array
    {
        return ['salesOrder', 'customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.inventoryAllocation'];
    }
}
