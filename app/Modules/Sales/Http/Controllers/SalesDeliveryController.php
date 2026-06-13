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
use Modules\Sales\Services\SalesDeliveryService;
use Modules\Sales\Services\SalesReturnSourceService;

final class SalesDeliveryController
{
    use ScopesSalesRequests;
    use FiltersSalesQueries;

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
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
        return (new SalesDeliveryResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $delivery): SalesDeliveryResource
    {
        return new SalesDeliveryResource($this->scope(SalesDelivery::query(), $request)->with($this->relations())->findOrFail($delivery));
    }

    public function post(SalesActionRequest $request, int $delivery, SalesDeliveryService $service): SalesDeliveryResource
    {
        return new SalesDeliveryResource($service->post($this->scope(SalesDelivery::query(), $request)->findOrFail($delivery), $request->currentUserId()));
    }

    public function reverse(SalesActionRequest $request, int $delivery, SalesDeliveryService $service): SalesDeliveryResource
    {
        return new SalesDeliveryResource($service->reverse($this->scope(SalesDelivery::query(), $request)->findOrFail($delivery), $request->currentUserId()));
    }

    public function returnableLines(
        ListSalesRequest $request,
        int $delivery,
        SalesReturnSourceService $sources,
    ): JsonResponse
    {
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
