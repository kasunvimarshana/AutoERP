<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesDeliveryRequest;
use Modules\Sales\Http\Resources\SalesDeliveryResource;
use Modules\Sales\Http\Resources\SalesReturnableDeliveryLineResource;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesDeliveryService;
use Modules\Sales\Services\SalesReturnSourceService;

final class SalesDeliveryController
{
    use FiltersSalesQueries;
    use ScopesSalesRequests;

    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::DELIVERIES_VIEW);

        $query = $this->scope(SalesDelivery::query(), $request)->with($this->relations());
        $this->applySalesFilters(
            $query,
            $request,
            'delivery_number',
            'delivery_date',
        );

        return SalesDeliveryResource::collection($query->latest('delivery_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesDeliveryRequest $request, SalesDeliveryService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::DELIVERIES_CREATE);

        return (new SalesDeliveryResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $delivery): SalesDeliveryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::DELIVERIES_VIEW);

        return new SalesDeliveryResource($this->scope(SalesDelivery::query(), $request)->with($this->relations())->findOrFail($delivery));
    }

    public function post(SalesActionRequest $request, int $delivery, SalesDeliveryService $service): SalesDeliveryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::DELIVERIES_POST);

        return new SalesDeliveryResource($service->post($this->scope(SalesDelivery::query(), $request)->findOrFail($delivery), $request->currentUserId()));
    }

    public function reverse(SalesActionRequest $request, int $delivery, SalesDeliveryService $service): SalesDeliveryResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::DELIVERIES_REVERSE);

        return new SalesDeliveryResource($service->reverse($this->scope(SalesDelivery::query(), $request)->findOrFail($delivery), $request->currentUserId()));
    }

    public function returnableLines(
        ListSalesRequest $request,
        int $delivery,
        SalesReturnSourceService $sources,
    ): JsonResponse {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RETURNS_VIEW);

        $model = $this->scope(SalesDelivery::query(), $request)
            ->with($this->relations())
            ->findOrFail($delivery);

        return response()->json([
            'data' => SalesReturnableDeliveryLineResource::collection(
                $sources->returnableDeliveryLines($model),
            )->resolve($request),
        ]);
    }

    private function relations(): array
    {
        return ['salesOrder', 'customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments'];
    }
}
