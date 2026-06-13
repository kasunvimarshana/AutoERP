<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Sales\Http\Controllers\Concerns\FiltersSalesQueries;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesOrderRequest;
use Modules\Sales\Http\Requests\UpdateSalesOrderRequest;
use Modules\Sales\Http\Resources\SalesOrderResource;
use Modules\Sales\Http\Resources\SalesOrderLineLookupResource;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Services\SalesOrderQuantityService;
use Modules\Sales\Services\SalesOrderService;

final class SalesOrderController
{
    use ScopesSalesRequests;
    use FiltersSalesQueries;

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(SalesOrder::query(), $request)->with($this->relations());
        $this->applySalesFilters(
            $query,
            $request,
            'sales_order_number',
            'sales_order_date',
        );

        return SalesOrderResource::collection($query->latest('sales_order_date')->paginate($request->perPage()));
    }

    public function store(StoreSalesOrderRequest $request, SalesOrderService $service): JsonResponse
    {
        return (new SalesOrderResource($service->create($request->toData())))->response()->setStatusCode(201);
    }

    public function show(ListSalesRequest $request, int $order): SalesOrderResource
    {
        return new SalesOrderResource($this->scope(SalesOrder::query(), $request)->with($this->relations())->findOrFail($order));
    }

    public function update(UpdateSalesOrderRequest $request, int $order, SalesOrderService $service): SalesOrderResource
    {
        return new SalesOrderResource($service->update($this->scope(SalesOrder::query(), $request)->findOrFail($order), $request->toData()));
    }

    public function destroy(SalesActionRequest $request, int $order, SalesOrderService $service): JsonResponse
    {
        $service->delete($this->scope(SalesOrder::query(), $request)->findOrFail($order));

        return response()->json(status: 204);
    }

    public function submit(SalesActionRequest $request, int $order, SalesOrderService $service): SalesOrderResource
    {
        return new SalesOrderResource($service->submit($this->scope(SalesOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function approve(SalesActionRequest $request, int $order, SalesOrderService $service): SalesOrderResource
    {
        return new SalesOrderResource($service->approve($this->scope(SalesOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function cancel(SalesActionRequest $request, int $order, SalesOrderService $service): SalesOrderResource
    {
        return new SalesOrderResource($service->cancel($this->scope(SalesOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function close(SalesActionRequest $request, int $order, SalesOrderService $service): SalesOrderResource
    {
        return new SalesOrderResource($service->close($this->scope(SalesOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function allocatableLines(
        ListSalesRequest $request,
        int $order,
        SalesOrderQuantityService $quantities,
    ): JsonResponse
    {
        return $this->lineLookup(
            $request,
            $order,
            fn (SalesOrderLine $line): bool => $quantities->isAllocatable($line),
        );
    }

    public function deliverableLines(
        ListSalesRequest $request,
        int $order,
        SalesOrderQuantityService $quantities,
    ): JsonResponse
    {
        return $this->lineLookup(
            $request,
            $order,
            fn (SalesOrderLine $line): bool => $quantities->isDeliverable($line),
        );
    }

    public function invoiceableLines(
        ListSalesRequest $request,
        int $order,
        SalesOrderQuantityService $quantities,
    ): JsonResponse
    {
        return $this->lineLookup(
            $request,
            $order,
            fn (SalesOrderLine $line): bool => $quantities->isInvoiceable($line),
        );
    }

    private function lineLookup(
        ListSalesRequest $request,
        int $order,
        callable $eligible,
    ): JsonResponse
    {
        $model = $this->scope(SalesOrder::query(), $request)
            ->with($this->relations())
            ->findOrFail($order);
        $lines = $model->lines->filter($eligible)->values();

        return response()->json([
            'data' => SalesOrderLineLookupResource::collection($lines)->resolve($request),
        ]);
    }

    private function relations(): array
    {
        return ['customer.creditProfile', 'quotation', 'warehouse', 'warehouseLocation', 'currency', 'lines.item', 'lines.variant', 'lines.orderedUom', 'lines.baseUom', 'adjustments'];
    }
}
