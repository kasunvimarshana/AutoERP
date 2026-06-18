<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreGoodsReceiptNoteRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptNoteResource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\GoodsReceiptNoteService;

final class GoodsReceiptNoteController
{
    use ScopesPurchaseRequests;

    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function index(ListPurchaseDocumentRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW);
        $this->assertAllowedStatus($request, GoodsReceiptNoteStatus::cases());

        $query = $this->scope(GoodsReceiptNote::query(), $request)->with($this->relations());

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
        if ($request->filled('date_from')) {
            $query->whereDate('received_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('received_date', '<=', $request->input('date_to'));
        }

        return GoodsReceiptNoteResource::collection($query->latest('received_date')->paginate($request->perPage()));
    }

    public function store(StoreGoodsReceiptNoteRequest $request, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_CREATE);

        return new GoodsReceiptNoteResource($service->create($request->toData())->load($this->relations()));
    }

    public function show(ListPurchaseDocumentRequest $request, int $grn): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_VIEW);

        return new GoodsReceiptNoteResource($this->scope(GoodsReceiptNote::query(), $request)
            ->with($this->relations())
            ->findOrFail($grn));
    }

    public function post(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_POST);

        return new GoodsReceiptNoteResource($service->post($this->scope(GoodsReceiptNote::query(), $request)->with('lines')->findOrFail($grn), $request->currentUserId())
            ->load($this->relations()));
    }

    public function reverse(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::GOODS_RECEIPTS_REVERSE);

        return new GoodsReceiptNoteResource($service->reverse($this->scope(GoodsReceiptNote::query(), $request)->findOrFail($grn), $request->currentUserId()));
    }

    public function returnableLines(ListPurchaseDocumentRequest $request, int $grn, DecimalMath $math): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_VIEW);

        $model = $this->scope(GoodsReceiptNote::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($grn);

        return response()->json(['data' => $model->lines
            ->filter(fn (GoodsReceiptNoteLine $line): bool => $math->compare(
                $math->sub((string) $line->accepted_quantity, (string) $line->returned_quantity),
                '0.000000',
            ) > 0)
            ->values()
            ->map(fn (GoodsReceiptNoteLine $line): array => [
                'id' => (int) $line->getKey(),
                'source_line_type' => 'goods_receipt_note_line',
                'source_line_id' => (int) $line->getKey(),
                'item' => $line->relationLoaded('item') ? ['id' => (int) $line->item->getKey(), 'code' => $line->item->code, 'name' => $line->item->name] : null,
                'uom' => $line->relationLoaded('uom') ? ['id' => (int) $line->uom->getKey(), 'code' => $line->uom->code, 'name' => $line->uom->name, 'symbol' => $line->uom->symbol] : null,
                'returnable_quantity' => $math->sub((string) $line->accepted_quantity, (string) $line->returned_quantity),
                'unit_price' => (string) $line->unit_price,
            ])
            ->all()]);
    }

    private function relations(): array
    {
        return ['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'lines.purchaseOrderLine', 'adjustments'];
    }
}
