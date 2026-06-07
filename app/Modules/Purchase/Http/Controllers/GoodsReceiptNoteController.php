<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\Http\Controllers\Concerns\ScopesPurchaseRequests;
use Modules\Purchase\Http\Requests\ListPurchaseOrderRequest;
use Modules\Purchase\Http\Requests\PurchaseActionRequest;
use Modules\Purchase\Http\Requests\StoreGoodsReceiptNoteRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptNoteResource;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Services\GoodsReceiptNoteService;

final class GoodsReceiptNoteController
{
    use ScopesPurchaseRequests;

    public function index(ListPurchaseOrderRequest $request): AnonymousResourceCollection
    {
        return GoodsReceiptNoteResource::collection($this->scope(GoodsReceiptNote::query(), $request)
            ->with($this->relations())
            ->latest('received_date')
            ->paginate($request->perPage()));
    }

    public function store(StoreGoodsReceiptNoteRequest $request, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->create($request->toData())->load($this->relations()));
    }

    public function show(ListPurchaseOrderRequest $request, int $grn): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($this->scope(GoodsReceiptNote::query(), $request)
            ->with($this->relations())
            ->findOrFail($grn));
    }

    public function post(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->post($this->scope(GoodsReceiptNote::query(), $request)->with('lines')->findOrFail($grn), $request->currentUserId())
            ->load($this->relations()));
    }

    public function reverse(PurchaseActionRequest $request, int $grn, GoodsReceiptNoteService $service): GoodsReceiptNoteResource
    {
        return new GoodsReceiptNoteResource($service->reverse($this->scope(GoodsReceiptNote::query(), $request)->findOrFail($grn), $request->currentUserId()));
    }

    public function returnableLines(ListPurchaseOrderRequest $request, int $grn, DecimalMath $math): JsonResponse
    {
        $model = $this->scope(GoodsReceiptNote::query(), $request)->with(['lines.item', 'lines.variant', 'lines.uom'])->findOrFail($grn);

        return response()->json(['data' => $model->lines
            ->filter(fn (GoodsReceiptNoteLine $line): bool => $math->compare((string) $line->remaining_quantity, '0.000000') > 0)
            ->values()
            ->map(fn (GoodsReceiptNoteLine $line): array => [
                'id' => (int) $line->getKey(),
                'source_line_type' => 'goods_receipt_note_line',
                'source_line_id' => (int) $line->getKey(),
                'item' => $line->relationLoaded('item') ? ['id' => (int) $line->item->getKey(), 'code' => $line->item->code, 'name' => $line->item->name] : null,
                'uom' => $line->relationLoaded('uom') ? ['id' => (int) $line->uom->getKey(), 'code' => $line->uom->code, 'name' => $line->uom->name, 'symbol' => $line->uom->symbol] : null,
                'returnable_quantity' => (string) $line->remaining_quantity,
                'unit_price' => (string) $line->unit_price,
            ])
            ->all()]);
    }

    private function relations(): array
    {
        return ['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments'];
    }
}
