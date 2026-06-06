<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Purchase\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Http\Requests\PreparePurchasePaymentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreGoodsReceiptNoteRequest;
use Modules\Purchase\Http\Requests\StorePurchaseInvoiceRequest;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\StorePurchaseReturnRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptNoteResource;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Http\Resources\PurchaseReturnResource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturn;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseInvoiceIntegrationService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\PurchasePaymentIntegrationService;
use Modules\Purchase\Services\PurchaseReturnService;

final class PurchaseController
{
    public function index(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(PurchaseOrder::query(), $request)->with(['lines', 'adjustments']);
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('purchase_order_number', 'like', "%{$search}%");
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

    public function store(StorePurchaseOrderRequest $request, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->create($request->toData()));
    }

    public function show(ListPurchaseOrderRequest $request, int $order): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->scope(PurchaseOrder::query(), $request)
            ->with(['lines', 'adjustments', 'goodsReceiptNotes.lines'])->findOrFail($order));
    }

    public function approve(PurchaseActionRequest $request, int $order, PurchaseOrderService $service): PurchaseOrderResource
    {
        return new PurchaseOrderResource($service->approve($this->scope(PurchaseOrder::query(), $request)->findOrFail($order), $request->currentUserId()));
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
        return new GoodsReceiptNoteResource($service->create($request->toData()));
    }

    public function postGrn(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->post($this->scope(GoodsReceiptNote::query(), $request)->with('lines')->findOrFail($grn), $request->currentUserId()));
    }

    public function createReturn(StorePurchaseReturnRequest $request, PurchaseReturnService $service): PurchaseReturnResource
    {
        return new PurchaseReturnResource($service->create($request->toData()));
    }

    public function postReturn(PurchaseActionRequest $request, int $return, PurchaseReturnService $service): JsonResponse
    {
        $model = $this->scope(PurchaseReturn::query(), $request)->with('lines')->findOrFail($return);

        return response()->json(['data' => get_object_vars($service->post($model, $request->currentUserId()))]);
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
        );

        return response()->json(['data' => $data]);
    }

    private function scope(Builder $query, ListPurchaseOrderRequest|PurchaseActionRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }
}
