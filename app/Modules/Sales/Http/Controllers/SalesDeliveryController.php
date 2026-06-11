<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesDeliveryRequest;
use Modules\Sales\Http\Resources\SalesDeliveryResource;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Services\SalesDeliveryService;

final class SalesDeliveryController
{
    use ScopesSalesRequests;

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(SalesDelivery::query(), $request)->with($this->relations());
        foreach (['status', 'customer_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

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

    public function returnableLines(ListSalesRequest $request, int $delivery, DecimalMath $math): JsonResponse
    {
        $model = $this->scope(SalesDelivery::query(), $request)->with($this->relations())->findOrFail($delivery);

        return response()->json(['data' => $model->lines->filter(function ($line) use ($math): bool {
            return $math->compare($math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity), '0.000000') > 0;
        })->values()->map(fn ($line): array => [
            'id' => (int) $line->getKey(),
            'source_line_type' => 'sales_delivery_line',
            'source_line_id' => (int) $line->getKey(),
            'item' => $line->item ? ['id' => (int) $line->item->getKey(), 'code' => $line->item->code, 'name' => $line->item->name] : null,
            'uom' => $line->uom ? ['id' => (int) $line->uom->getKey(), 'code' => $line->uom->code, 'name' => $line->uom->name] : null,
            'returnable_quantity' => $math->sub((string) $line->delivered_quantity, (string) $line->returned_quantity),
            'unit_price' => (string) $line->unit_price,
        ])->all()]);
    }

    private function relations(): array
    {
        return ['salesOrder', 'customer', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments'];
    }
}
