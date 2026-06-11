<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Services\DecimalMath;
use Modules\Sales\Http\Controllers\Concerns\ScopesSalesRequests;
use Modules\Sales\Http\Requests\ListSalesRequest;
use Modules\Sales\Http\Requests\SalesActionRequest;
use Modules\Sales\Http\Requests\StoreSalesOrderRequest;
use Modules\Sales\Http\Requests\UpdateSalesOrderRequest;
use Modules\Sales\Http\Resources\SalesOrderResource;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\SalesOrderService;

final class SalesOrderController
{
    use ScopesSalesRequests;

    public function index(ListSalesRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(SalesOrder::query(), $request)->with($this->relations());
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('sales_order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('customer_number', 'like', "%{$search}%"));
            });
        }
        foreach (['status', 'customer_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('sales_order_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sales_order_date', '<=', $request->input('date_to'));
        }

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

    public function allocatableLines(ListSalesRequest $request, int $order, DecimalMath $math): JsonResponse
    {
        return $this->lineLookup($request, $order, 'remaining_allocatable_quantity', $math);
    }

    public function deliverableLines(ListSalesRequest $request, int $order, DecimalMath $math): JsonResponse
    {
        return $this->lineLookup($request, $order, 'remaining_deliverable_quantity', $math);
    }

    public function invoiceableLines(ListSalesRequest $request, int $order, DecimalMath $math): JsonResponse
    {
        $model = $this->scope(SalesOrder::query(), $request)->with($this->relations())->findOrFail($order);

        return response()->json(['data' => $model->lines->filter(function ($line) use ($math): bool {
            $basis = $math->compare((string) $line->delivered_quantity, '0.000000') > 0
                ? (string) $line->delivered_quantity
                : (string) $line->ordered_quantity;

            return $math->compare($math->sub($basis, (string) $line->invoiced_quantity), '0.000000') > 0;
        })->values()->map(fn ($line): array => $this->lineSummary($line))->all()]);
    }

    private function lineLookup(ListSalesRequest $request, int $order, string $column, DecimalMath $math): JsonResponse
    {
        $model = $this->scope(SalesOrder::query(), $request)->with($this->relations())->findOrFail($order);

        return response()->json(['data' => $model->lines
            ->filter(fn ($line): bool => $math->compare((string) $line->{$column}, '0.000000') > 0)
            ->values()
            ->map(fn ($line): array => $this->lineSummary($line))
            ->all()]);
    }

    private function lineSummary($line): array
    {
        return [
            'id' => (int) $line->getKey(),
            'sales_order_line_id' => (int) $line->getKey(),
            'item' => $line->item ? ['id' => (int) $line->item->getKey(), 'code' => $line->item->code, 'name' => $line->item->name] : null,
            'uom' => $line->orderedUom ? ['id' => (int) $line->orderedUom->getKey(), 'code' => $line->orderedUom->code, 'name' => $line->orderedUom->name, 'symbol' => $line->orderedUom->symbol] : null,
            'ordered_quantity' => (string) $line->ordered_quantity,
            'allocated_quantity' => (string) $line->allocated_quantity,
            'delivered_quantity' => (string) $line->delivered_quantity,
            'invoiced_quantity' => (string) $line->invoiced_quantity,
            'remaining_allocatable_quantity' => (string) $line->remaining_allocatable_quantity,
            'remaining_deliverable_quantity' => (string) $line->remaining_deliverable_quantity,
            'remaining_invoiceable_quantity' => (string) $line->remaining_invoiceable_quantity,
            'unit_price' => (string) $line->unit_price,
        ];
    }

    private function relations(): array
    {
        return ['customer.creditProfile', 'quotation', 'warehouse', 'warehouseLocation', 'currency', 'lines.item', 'lines.variant', 'lines.orderedUom', 'lines.baseUom', 'adjustments'];
    }
}
