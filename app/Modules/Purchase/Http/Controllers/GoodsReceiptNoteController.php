<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Purchase\Constants\PurchaseAuditEvent;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreGoodsReceiptNoteRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptNoteResource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseAuditService;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseDocumentPresentationService;
use Modules\Purchase\Services\PurchaseGoodsReceiptPostingCoordinator;
use Modules\Purchase\Services\PurchaseProcurementBalanceService;

final class GoodsReceiptNoteController
{
    use ScopesPurchaseRequests;

    public function __construct(
        private readonly PurchaseAuthorizationService $authorization,
        private readonly PurchaseDocumentPresentationService $presentation,
        private readonly PurchaseAuditService $audit,
    ) {}

    public function index(ListPurchaseDocumentRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW);
        $this->assertAllowedStatus($request, GoodsReceiptNoteStatus::cases());

        $query = $this->scope(GoodsReceiptNote::query(), $request)
            ->select('goods_receipt_notes.*')
            ->with($this->relations());
        $this->addCapabilityProjection($query);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('grn_number', 'like', "%{$search}%")
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
        $this->applyProgressFilters($query, $request);
        if ($request->filled('date_from')) {
            $query->whereDate('received_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('received_date', '<=', $request->input('date_to'));
        }

        $goodsReceipts = $query->latest('received_date')->paginate($request->perPage());
        $this->presentation->prepareGoodsReceipts($goodsReceipts->getCollection());

        return GoodsReceiptNoteResource::collection($goodsReceipts);
    }

    public function store(StoreGoodsReceiptNoteRequest $request, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_CREATE);

        $grn = $service->create($request->toData())->load($this->relations());
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::GOODS_RECEIPT_CREATED, 'goods_receipt_note', $grn);

        return new GoodsReceiptNoteResource($this->presentation->prepareGoodsReceipt($grn));
    }

    public function show(ListPurchaseDocumentRequest $request, int $grn): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW);

        $grn = $this->scope(GoodsReceiptNote::query(), $request)
            ->with($this->relations())
            ->findOrFail($grn);

        return new GoodsReceiptNoteResource($this->presentation->prepareGoodsReceipt($grn));
    }

    public function post(PurchaseActionRequest $request, int $grn, PurchaseGoodsReceiptPostingCoordinator $coordinator): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_POST);

        $model = $this->scope(GoodsReceiptNote::query(), $request)->with('lines')->findOrFail($grn);
        $before = $model->attributesToArray();
        $updated = $coordinator->post(
            $model,
            $request->currentUserId(),
            $request->expectedVersion(),
        )->load($this->relations());
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::GOODS_RECEIPT_POSTED, 'goods_receipt_note', $updated, $before);

        return new GoodsReceiptNoteResource($this->presentation->prepareGoodsReceipt($updated));
    }

    public function reverse(PurchaseActionRequest $request, int $grn, PurchaseGoodsReceiptPostingCoordinator $coordinator): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_REVERSE);

        $model = $this->scope(GoodsReceiptNote::query(), $request)->findOrFail($grn);
        $before = $model->attributesToArray();
        $updated = $coordinator->reverse(
            $model,
            $request->reversalDate(),
            $request->reversalReason(),
            $request->currentUserId(),
            $request->expectedVersion(),
        );
        $this->audit->recordDocumentEvent(PurchaseAuditEvent::GOODS_RECEIPT_REVERSED, 'goods_receipt_note', $updated, $before);

        return new GoodsReceiptNoteResource($this->presentation->prepareGoodsReceipt($updated));
    }

    private function relations(): array
    {
        return ['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.purchaseOrderLine', 'lines.batchAllocations.batch', 'adjustments'];
    }

    private function applyProgressFilters(Builder $query, ListPurchaseDocumentRequest $request): void
    {
        $balances = app(PurchaseProcurementBalanceService::class);
        foreach (['invoice_status', 'return_status'] as $filter) {
            if ($request->filled($filter)) {
                $balances->applyGoodsReceiptProgressFilter($query, $filter, (string) $request->input($filter));
            }
        }
    }

    private function addCapabilityProjection(Builder $query): void
    {
        $query->selectSub(function ($sub): void {
            $sub->from('purchase_returns')
                ->selectRaw('COUNT(*)')
                ->where('purchase_returns.source_type', 'goods_receipt_note')
                ->whereColumn('purchase_returns.source_id', 'goods_receipt_notes.id')
                ->where('purchase_returns.status', '!=', 'cancelled');
        }, 'unresolved_purchase_returns_count');
    }
}
