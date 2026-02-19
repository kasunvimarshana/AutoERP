<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Events\PurchaseOrderApproved;
use Modules\Purchase\Events\PurchaseOrderCancelled;
use Modules\Purchase\Events\PurchaseOrderConfirmed;
use Modules\Purchase\Events\PurchaseOrderCreated;
use Modules\Purchase\Events\PurchaseOrderSent;
use Modules\Purchase\Http\Requests\StorePurchaseOrderRequest;
use Modules\Purchase\Http\Requests\UpdatePurchaseOrderRequest;
use Modules\Purchase\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Repositories\PurchaseOrderRepository;
use Modules\Purchase\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService,
        private PurchaseOrderRepository $purchaseOrderRepository
    ) {}

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        $filters = [
            'status' => $request->input('status'),
            'vendor_id' => $request->input('vendor_id'),
            'organization_id' => $request->input('organization_id'),
            'from_date' => $request->input('date_from'),
            'to_date' => $request->input('date_to'),
            'search' => $request->input('search'),
            'tenant_id' => $request->user()->currentTenant()->id,
        ];

        $perPage = $request->get('per_page', 15);
        $purchaseOrders = $this->purchaseOrderRepository->getFiltered($filters, $perPage);

        return ApiResponse::paginated(
            $purchaseOrders->setCollection(
                $purchaseOrders->getCollection()->map(fn ($po) => new PurchaseOrderResource($po))
            ),
            'Purchase orders retrieved successfully'
        );
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorize('create', PurchaseOrder::class);

        $data = $request->validated();
        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['status'] = PurchaseOrderStatus::DRAFT;
        $data['created_by'] = $request->user()->id;

        $purchaseOrder = $this->purchaseOrderService->create($data);
        event(new PurchaseOrderCreated($purchaseOrder));
        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::created(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order created successfully'
        );
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order retrieved successfully'
        );
    }

    /**
     * Update the specified purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('update', $purchaseOrder);

        if (! $purchaseOrder->status->canModify()) {
            return ApiResponse::error(
                'Purchase order cannot be modified in its current status',
                422
            );
        }

        $data = $request->validated();
        $purchaseOrder = $this->purchaseOrderService->update($purchaseOrder->id, $data);
        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order updated successfully'
        );
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('delete', $purchaseOrder);

        if (! $purchaseOrder->status->canModify()) {
            return ApiResponse::error(
                'Purchase order cannot be deleted in its current status',
                422
            );
        }

        $this->purchaseOrderService->delete($purchaseOrder->id);

        return ApiResponse::success(
            null,
            'Purchase order deleted successfully'
        );
    }

    /**
     * Approve the purchase order.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('approve', $purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrderStatus::PENDING) {
            return ApiResponse::error(
                'Only pending purchase orders can be approved',
                422
            );
        }

        $purchaseOrder = $this->purchaseOrderService->approve($purchaseOrder->id, $request->user()->id);
        event(new PurchaseOrderApproved($purchaseOrder));
        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order approved successfully'
        );
    }

    /**
     * Send the purchase order to vendor.
     */
    public function send(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('send', $purchaseOrder);

        if (! in_array($purchaseOrder->status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::SENT])) {
            return ApiResponse::error(
                'Only approved purchase orders can be sent',
                422
            );
        }

        $purchaseOrder = $this->purchaseOrderService->send($purchaseOrder->id);
        event(new PurchaseOrderSent($purchaseOrder));
        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order sent successfully'
        );
    }

    /**
     * Confirm the purchase order receipt from vendor.
     */
    public function confirm(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('confirm', $purchaseOrder);

        if ($purchaseOrder->status !== PurchaseOrderStatus::SENT) {
            return ApiResponse::error(
                'Only sent purchase orders can be confirmed',
                422
            );
        }

        $purchaseOrder = $this->purchaseOrderService->confirm($purchaseOrder->id);
        event(new PurchaseOrderConfirmed($purchaseOrder));
        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order confirmed successfully'
        );
    }

    /**
     * Cancel the purchase order.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorize('cancel', $purchaseOrder);

        if (! $purchaseOrder->status->canCancel()) {
            return ApiResponse::error(
                'Purchase order cannot be cancelled in its current status',
                422
            );
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseOrder = $this->purchaseOrderService->cancel($purchaseOrder->id, $request->input('reason'));
        event(new PurchaseOrderCancelled($purchaseOrder));
        $purchaseOrder->load(['vendor', 'items.product', 'items.unit']);

        return ApiResponse::success(
            new PurchaseOrderResource($purchaseOrder),
            'Purchase order cancelled successfully'
        );
    }
}
