<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Purchase\Enums\GoodsReceiptStatus;
use Modules\Purchase\Events\GoodsReceiptCancelled;
use Modules\Purchase\Events\GoodsReceiptConfirmed;
use Modules\Purchase\Events\GoodsReceiptCreated;
use Modules\Purchase\Events\GoodsReceiptPosted;
use Modules\Purchase\Http\Requests\StoreGoodsReceiptRequest;
use Modules\Purchase\Http\Resources\GoodsReceiptResource;
use Modules\Purchase\Models\GoodsReceipt;
use Modules\Purchase\Services\GoodsReceiptService;

class GoodsReceiptController extends Controller
{
    public function __construct(
        private GoodsReceiptService $goodsReceiptService
    ) {}

    /**
     * Display a listing of goods receipts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $query = GoodsReceipt::query()
            ->with(['vendor', 'purchaseOrder', 'items.product', 'items.unit'])
            ->where('tenant_id', $request->user()->currentTenant()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->has('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->has('date_from')) {
            $query->where('receipt_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('receipt_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('gr_code', 'like', "%{$search}%")
                    ->orWhere('delivery_note', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->get('per_page', 15);
        $goodsReceipts = $query->latest('receipt_date')->paginate($perPage);

        return ApiResponse::paginated(
            $goodsReceipts->setCollection(
                $goodsReceipts->getCollection()->map(fn ($gr) => new GoodsReceiptResource($gr))
            ),
            'Goods receipts retrieved successfully'
        );
    }

    /**
     * Store a newly created goods receipt.
     */
    public function store(StoreGoodsReceiptRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['status'] = GoodsReceiptStatus::DRAFT;
        $data['created_by'] = $request->user()->id;

        $goodsReceipt = DB::transaction(function () use ($data, $items) {
            $goodsReceipt = $this->goodsReceiptService->create(
                $data['purchase_order_id'],
                $data,
                $items
            );
            event(new GoodsReceiptCreated($goodsReceipt));

            return $goodsReceipt;
        });

        $goodsReceipt->load(['vendor', 'purchaseOrder', 'items.product', 'items.unit']);

        return ApiResponse::created(
            new GoodsReceiptResource($goodsReceipt),
            'Goods receipt created successfully'
        );
    }

    /**
     * Display the specified goods receipt.
     */
    public function show(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('view', $goodsReceipt);

        $goodsReceipt->load(['vendor', 'purchaseOrder', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new GoodsReceiptResource($goodsReceipt),
            'Goods receipt retrieved successfully'
        );
    }

    /**
     * Update the specified goods receipt.
     */
    public function update(Request $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('update', $goodsReceipt);

        if ($goodsReceipt->status !== GoodsReceiptStatus::DRAFT) {
            return ApiResponse::error(
                'Only draft goods receipts can be updated',
                422
            );
        }

        $data = $request->validate([
            'receipt_date' => ['sometimes', 'required', 'date'],
            'delivery_note' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $goodsReceipt = DB::transaction(function () use ($goodsReceipt, $data) {
            return $this->goodsReceiptService->update($goodsReceipt->id, $data);
        });

        $goodsReceipt->load(['vendor', 'purchaseOrder', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new GoodsReceiptResource($goodsReceipt),
            'Goods receipt updated successfully'
        );
    }

    /**
     * Remove the specified goods receipt.
     */
    public function destroy(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('delete', $goodsReceipt);

        if ($goodsReceipt->status !== GoodsReceiptStatus::DRAFT) {
            return ApiResponse::error(
                'Only draft goods receipts can be deleted',
                422
            );
        }

        DB::transaction(function () use ($goodsReceipt) {
            $this->goodsReceiptService->delete($goodsReceipt->id);
        });

        return ApiResponse::success(
            null,
            'Goods receipt deleted successfully'
        );
    }

    /**
     * Confirm the goods receipt.
     */
    public function confirm(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('confirm', $goodsReceipt);

        if ($goodsReceipt->status !== GoodsReceiptStatus::DRAFT) {
            return ApiResponse::error(
                'Only draft goods receipts can be confirmed',
                422
            );
        }

        $goodsReceipt = DB::transaction(function () use ($goodsReceipt) {
            $goodsReceipt = $this->goodsReceiptService->confirm($goodsReceipt->id);
            event(new GoodsReceiptConfirmed($goodsReceipt));

            return $goodsReceipt;
        });

        $goodsReceipt->load(['vendor', 'purchaseOrder', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new GoodsReceiptResource($goodsReceipt),
            'Goods receipt confirmed successfully'
        );
    }

    /**
     * Post goods receipt to inventory.
     */
    public function postToInventory(GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('postToInventory', $goodsReceipt);

        if ($goodsReceipt->status !== GoodsReceiptStatus::CONFIRMED) {
            return ApiResponse::error(
                'Only confirmed goods receipts can be posted to inventory',
                422
            );
        }

        $goodsReceipt = DB::transaction(function () use ($goodsReceipt) {
            $goodsReceipt = $this->goodsReceiptService->postToInventory($goodsReceipt->id);
            event(new GoodsReceiptPosted($goodsReceipt));

            return $goodsReceipt;
        });

        $goodsReceipt->load(['vendor', 'purchaseOrder', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new GoodsReceiptResource($goodsReceipt),
            'Goods receipt posted to inventory successfully'
        );
    }

    /**
     * Cancel the goods receipt.
     */
    public function cancel(Request $request, GoodsReceipt $goodsReceipt): JsonResponse
    {
        $this->authorize('cancel', $goodsReceipt);

        if (! in_array($goodsReceipt->status, [GoodsReceiptStatus::DRAFT, GoodsReceiptStatus::CONFIRMED])) {
            return ApiResponse::error(
                'Goods receipt cannot be cancelled in its current status',
                422
            );
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $goodsReceipt = DB::transaction(function () use ($goodsReceipt, $request) {
            $goodsReceipt = $this->goodsReceiptService->cancel($goodsReceipt->id, $request->input('reason'));
            event(new GoodsReceiptCancelled($goodsReceipt));

            return $goodsReceipt;
        });

        $goodsReceipt->load(['vendor', 'purchaseOrder', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new GoodsReceiptResource($goodsReceipt),
            'Goods receipt cancelled successfully'
        );
    }
}
