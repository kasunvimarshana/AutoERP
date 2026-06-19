<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesReturnRequest;
use Modules\Sales\Http\Resources\SalesReturnResource;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesReturnService;

final class SalesReturnController
{
    use FiltersSalesQueries;
    use ScopesSalesRequests;

    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_VIEW);

        $query = $this->scope(SalesReturn::query(), $request)->with($this->relations());
        $this->applySalesFilters(
            $query,
            $request,
            'return_number',
            'return_date',
        );

        return SalesReturnResource::collection($query->latest('return_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesReturnRequest $request, SalesReturnService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_CREATE);

        return (new SalesReturnResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $return): SalesReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_VIEW);

        return new SalesReturnResource($this->scope(SalesReturn::query(), $request)->with($this->relations())->findOrFail($return));
    }

    public function approve(SalesActionRequest $request, int $return, SalesReturnService $service): SalesReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_APPROVE);

        return new SalesReturnResource($service->approve($this->scope(SalesReturn::query(), $request)->findOrFail($return), $request->currentUserId()));
    }

    public function post(SalesActionRequest $request, int $return, SalesReturnService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_POST);

        return response()->json(['data' => get_object_vars($service->post(
            $this->scope(SalesReturn::query(), $request)->findOrFail($return),
            $request->currentUserId(),
        ))]);
    }

    public function cancel(SalesActionRequest $request, int $return, SalesReturnService $service): SalesReturnResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_CANCEL);

        return new SalesReturnResource($service->cancel($this->scope(SalesReturn::query(), $request)->findOrFail($return), $request->currentUserId()));
    }

    private function relations(): array
    {
        return ['customer', 'warehouse', 'warehouseLocation', 'replacementSalesOrder', 'creditNote', 'lines.item', 'lines.variant', 'lines.uom', 'adjustmentAllocations'];
    }
}
