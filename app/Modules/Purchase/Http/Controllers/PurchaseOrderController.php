<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchase\Http\Resources\PurchaseOrderLineResource;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Supplier\Http\Resources\SupplierItemMappingResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;

final class PurchaseOrderController
{
    use ScopesPurchaseRequests;

    public function index(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(PurchaseOrder::query(), $request)->with([
            'supplier', 'warehouse', 'warehouseLocation', 'currency', 'createdBy', 'approvedBy',
            'lines.item', 'lines.variant', 'lines.uom', 'adjustments',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('purchase_order_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $supplier) use ($search): void {
                        $supplier->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('supplier_number', 'like', "%{$search}%");
                    });
            });
        }

        foreach (['status', 'supplier_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_order_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_order_date', '<=', $request->input('date_to'));
        }

        return PurchaseOrderResource::collection($query->latest('purchase_order_date')->paginate($request->perPage()));
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): JsonResponse
    {
        return (new PurchaseOrderResource($service->create($request->toData())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ListPurchaseOrderRequest $request, int $order): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->scope(PurchaseOrder::query(), $request)
            ->with([
                'supplier', 'warehouse', 'warehouseLocation', 'currency', 'createdBy', 'approvedBy', 'closedBy',
                'lines.item', 'lines.variant', 'lines.uom', 'adjustments',
            ])->findOrFail($order));
    }

    public function update(UpdatePurchaseOrderRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->update($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->toData()));
    }

    public function destroy(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): JsonResponse
    {
        $service->delete($this->scope(PurchaseOrder::query(), $request)->findOrFail($order));

        return response()->json(status: 204);
    }

    public function approve(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->approve($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function submit(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->submit($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function cancel(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->cancel($this->scope(PurchaseOrder::query(), $request)->findOrFail($order)));
    }

    public function close(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->close($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
    }

    public function receivableLines(ListPurchaseOrderRequest $request, int $order, DecimalMath $math): JsonResponse
    {
        $model = $this->scope(PurchaseOrder::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($order);

        return response()->json(['data' => $model->lines
            ->filter(fn ($line): bool => $math->compare((string) $line->remaining_receivable_quantity, '0.000000') > 0)
            ->values()
            ->map(fn ($line): array => (new PurchaseOrderLineResource($line))->resolve($request))
            ->all()]);
    }

    public function invoiceableLines(ListPurchaseOrderRequest $request, int $order, DecimalMath $math): JsonResponse
    {
        $model = $this->scope(PurchaseOrder::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($order);

        return response()->json(['data' => $model->lines->filter(function ($line) use ($math): bool {
            $basis = $math->compare((string) $line->received_quantity, '0.000000') > 0
                ? (string) $line->received_quantity
                : (string) $line->ordered_quantity;

            return $math->compare($math->sub($basis, (string) $line->invoiced_quantity), '0.000000') > 0;
        })->values()->map(fn ($line): array => (new PurchaseOrderLineResource($line))->resolve($request))->all()]);
    }

    public function supplierItemMappings(ListPurchaseOrderRequest $request, int $supplier): AnonymousResourceCollection
    {
        $supplierModel = $this->scope(Supplier::query(), $request)->findOrFail($supplier);

        return SupplierItemMappingResource::collection(SupplierItemMapping::query()
            ->where('supplier_id', $supplierModel->getKey())
            ->with(['item', 'variant', 'defaultPurchaseUom'])
            ->where('is_active', true)
            ->paginate($request->perPage()));
    }
}
