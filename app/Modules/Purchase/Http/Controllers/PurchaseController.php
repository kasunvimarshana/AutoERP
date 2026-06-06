<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Purchase\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Http\Requests\PreparePurchasePaymentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreGoodsReceiptNoteRequest;
use Modules\Purchase\Http\Requests\StorePurchaseDebitNoteRequest;
use Modules\Purchase\Http\Requests\StorePurchaseInvoiceRequest;
use Modules\Purchase\Http\Requests\StorePurchaseInventoryAdjustmentRequest;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptNoteResource;
use Modules\Purchase\Http\Resources\PurchaseDebitNoteResource;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Http\Resources\PurchaseReturnResource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Services\PurchaseDebitNoteService;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseInvoiceIntegrationService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\PurchasePaymentIntegrationService;
use Modules\Purchase\Services\PurchaseReturnService;
use Modules\Supplier\Http\Resources\SupplierItemMappingResource;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierItemMapping;

final class PurchaseController
{
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

    public function createGrn(StoreGoodsReceiptNoteRequest $request, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->create($request->toData())->load(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments']));
    }

    public function grnIndex(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        return GoodsReceiptNoteResource::collection($this->scope(GoodsReceiptNote::query(), $request)
            ->with(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments'])
            ->latest('received_date')
            ->paginate($request->perPage()));
    }

    public function showGrn(ListPurchaseOrderRequest $request, int $grn): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($this->scope(GoodsReceiptNote::query(), $request)
            ->with(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments'])
            ->findOrFail($grn));
    }

    public function postGrn(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->post($this->scope(GoodsReceiptNote::query(), $request)->with('lines')->findOrFail($grn), $request->currentUserId())
            ->load(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments']));
    }

    public function reverseGrn(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->reverse($this->scope(GoodsReceiptNote::query(), $request)->findOrFail($grn), $request->currentUserId()));
    }

    public function createReturn(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->create($request->toData()));
    }

    public function returnIndex(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        return PurchaseReturnResource::collection($this->scope(PurchaseReturn::query(), $request)
            ->with(['supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustmentAllocations'])
            ->latest('return_date')
            ->paginate($request->perPage()));
    }

    public function showReturn(ListPurchaseOrderRequest $request, int $return): PurchaseReturnResource
    {
        return new PurchaseReturnResource($this->scope(PurchaseReturn::query(), $request)
            ->with(['supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustmentAllocations'])
            ->findOrFail($return));
    }

    public function approveReturn(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->approve($this->scope(PurchaseReturn::query(), $request)->findOrFail($return), $request->currentUserId()));
    }

    public function postReturn(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): JsonResponse
    {
        $model = $this->scope(PurchaseReturn::query(), $request)->with('lines')->findOrFail($return);

        return response()->json(['data' => get_object_vars($service->post($model, $request->currentUserId()))]);
    }

    public function cancelReturn(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->cancel($this->scope(PurchaseReturn::query(), $request)->findOrFail($return)));
    }

    public function createManualSupplierReturn(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        $data = $request->toData();

        return new PurchaseReturnResource($service->create(new CreatePurchaseReturnData(
            tenantId: $data->tenantId,
            returnDate: $data->returnDate,
            warehouseId: $data->warehouseId,
            organizationUnitId: $data->organizationUnitId,
            returnNumber: $data->returnNumber,
            warehouseLocationId: $data->warehouseLocationId,
            supplierType: $data->supplierType,
            supplierId: $data->supplierId,
            reason: $data->reason,
            returnType: PurchaseReturnType::ManualSupplierReturn,
            sourceType: 'manual_supplier_return',
            sourceId: $data->sourceId,
            approvalRequired: true,
            affectsSupplierBalance: $data->affectsSupplierBalance,
            costBasis: $data->costBasis,
            auditMetadata: $data->auditMetadata,
            createdBy: $data->createdBy,
            lines: $data->lines,
        )));
    }

    public function createDebitNote(StorePurchaseDebitNoteRequest $request, PurchaseDebitNoteService $service): JsonResponse
    {
        return (new PurchaseDebitNoteResource($service->create($request->toData())->load(['supplier', 'purchaseReturn'])))
            ->response()
            ->setStatusCode(201);
    }

    public function debitNoteIndex(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        return PurchaseDebitNoteResource::collection($this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->latest('debit_note_date')
            ->paginate($request->perPage()));
    }

    public function showDebitNote(ListPurchaseOrderRequest $request, int $debitNote): PurchaseDebitNoteResource
    {
        return new PurchaseDebitNoteResource($this->scope(PurchaseDebitNote::query(), $request)
            ->with(['supplier', 'purchaseReturn'])
            ->findOrFail($debitNote));
    }

    public function createInventoryAdjustmentRequest(StorePurchaseInventoryAdjustmentRequest $request, StockAdjustmentService $service): JsonResponse
    {
        return (new InventoryAdjustmentResource($service->create($request->toData())))
            ->response()
            ->setStatusCode(201);
    }

    public function previewInvoice(StorePurchaseInvoiceRequest $request, PurchaseInvoiceIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => get_object_vars($service->previewSupplierInvoice($request->toData()))]);
    }

    public function createInvoice(StorePurchaseInvoiceRequest $request, PurchaseInvoiceIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => $service->createSupplierInvoice($request->toData())], 201);
    }

    public function preparePayment(PreparePurchasePaymentRequest $request, PurchasePaymentIntegrationService $service): JsonResponse
    {
        $data = $service->prepareSupplierPayment(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            supplierType: $request->filled('supplier_type') ? (string) $request->input('supplier_type') : null,
            supplierId: $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            currencyId: $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            exchangeRate: (string) $request->input('exchange_rate', '1.000000'),
            referenceNumber: $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            allocations: array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
                invoiceId: (int) $row['invoice_id'],
                allocatedAmount: (string) $row['allocated_amount'],
                allocationDate: (string) ($row['allocation_date'] ?? $request->input('payment_date')),
            ), $request->input('allocations', [])),
        );

        return response()->json(['data' => $data]);
    }

    public function receivableLines(ListPurchaseOrderRequest $request, int $order): JsonResponse
    {
        $model = $this->scope(PurchaseOrder::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($order);
        $math = app(\Modules\Core\Services\DecimalMath::class);

        return response()->json(['data' => $model->lines->filter(fn ($line): bool => $math->compare((string) $line->remaining_receivable_quantity, '0.000000') > 0)->values()->map(fn ($line): array => (new \Modules\Purchase\Http\Resources\PurchaseOrderLineResource($line))->resolve($request))->all()]);
    }

    public function invoiceableLines(ListPurchaseOrderRequest $request, int $order): JsonResponse
    {
        $model = $this->scope(PurchaseOrder::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($order);
        $math = app(\Modules\Core\Services\DecimalMath::class);

        return response()->json(['data' => $model->lines->filter(function ($line) use ($math): bool {
            $basis = $math->compare((string) $line->received_quantity, '0.000000') > 0
                ? (string) $line->received_quantity
                : (string) $line->ordered_quantity;

            return $math->compare($math->sub($basis, (string) $line->invoiced_quantity), '0.000000') > 0;
        })->values()->map(fn ($line): array => (new \Modules\Purchase\Http\Resources\PurchaseOrderLineResource($line))->resolve($request))->all()]);
    }

    public function returnableLines(ListPurchaseOrderRequest $request, int $grn): JsonResponse
    {
        $model = $this->scope(GoodsReceiptNote::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($grn);

        return response()->json(['data' => $model->lines->filter(fn (GoodsReceiptNoteLine $line): bool => app(\Modules\Core\Services\DecimalMath::class)->compare((string) $line->remaining_quantity, '0.000000') > 0)->values()->map(fn (GoodsReceiptNoteLine $line): array => [
            'id' => (int) $line->getKey(),
            'source_line_type' => 'goods_receipt_note_line',
            'source_line_id' => (int) $line->getKey(),
            'item' => $line->relationLoaded('item') ? ['id' => (int) $line->item->getKey(), 'code' => $line->item->code, 'name' => $line->item->name] : null,
            'uom' => $line->relationLoaded('uom') ? ['id' => (int) $line->uom->getKey(), 'code' => $line->uom->code, 'name' => $line->uom->name, 'symbol' => $line->uom->symbol] : null,
            'returnable_quantity' => (string) $line->remaining_quantity,
            'unit_price' => (string) $line->unit_price,
        ])->all()]);
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

    private function scope(Builder $query, TenantScopedRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
