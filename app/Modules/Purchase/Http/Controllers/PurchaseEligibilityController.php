<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptNoteResource;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseSourceEligibilityService;

final class PurchaseEligibilityController
{
    use ScopesPurchaseRequests;

    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function receivablePurchaseOrders(ListPurchaseDocumentRequest $request, PurchaseSourceEligibilityService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW);

        return PurchaseOrderResource::collection($service->receivablePurchaseOrders(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            trim((string) $request->input('search', '')),
            $request->perPage(),
        ));
    }

    public function invoiceablePurchaseOrders(ListPurchaseDocumentRequest $request, PurchaseSourceEligibilityService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW);

        return PurchaseOrderResource::collection($service->invoiceablePurchaseOrders(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            trim((string) $request->input('search', '')),
            $request->perPage(),
        ));
    }

    public function invoiceableGoodsReceipts(ListPurchaseDocumentRequest $request, PurchaseSourceEligibilityService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW);

        return GoodsReceiptNoteResource::collection($service->invoiceableGoodsReceipts(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            trim((string) $request->input('search', '')),
            $request->perPage(),
        ));
    }

    public function returnableGoodsReceipts(ListPurchaseDocumentRequest $request, PurchaseSourceEligibilityService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_VIEW);

        return GoodsReceiptNoteResource::collection($service->returnableGoodsReceipts(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            trim((string) $request->input('search', '')),
            $request->perPage(),
        ));
    }

    public function outstandingSupplierInvoices(ListPurchaseDocumentRequest $request, PurchaseSourceEligibilityService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::PAYMENTS_VIEW);

        return InvoiceResource::collection($service->outstandingSupplierInvoices(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            trim((string) $request->input('search', '')),
            $request->perPage(),
        ));
    }

    public function receivableLines(ListPurchaseDocumentRequest $request, int $order, PurchaseSourceEligibilityService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW);

        $model = $this->scope(PurchaseOrder::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($order);

        return response()->json(['data' => $service->receivableLines($model)]);
    }

    public function invoiceableOrderLines(ListPurchaseDocumentRequest $request, int $order, PurchaseSourceEligibilityService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW);

        $model = $this->scope(PurchaseOrder::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($order);

        return response()->json(['data' => $service->invoiceableOrderLines($model)]);
    }

    public function invoiceableGoodsReceiptLines(ListPurchaseDocumentRequest $request, int $grn, PurchaseSourceEligibilityService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW);

        $model = $this->scope(GoodsReceiptNote::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom', 'lines.purchaseOrderLine.order'])->findOrFail($grn);

        return response()->json(['data' => $service->invoiceableGoodsReceiptLines($model)]);
    }

    public function returnableGoodsReceiptLines(ListPurchaseDocumentRequest $request, int $grn, PurchaseSourceEligibilityService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_VIEW);

        $model = $this->scope(GoodsReceiptNote::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom', 'lines.purchaseOrderLine.order'])->findOrFail($grn);

        return response()->json(['data' => $service->returnableGoodsReceiptLines($model)]);
    }
}
