<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Purchase\Enums\BillStatus;
use Modules\Purchase\Events\BillCancelled;
use Modules\Purchase\Events\BillCreated;
use Modules\Purchase\Events\BillPaid;
use Modules\Purchase\Events\BillPaymentRecorded;
use Modules\Purchase\Events\BillSent;
use Modules\Purchase\Http\Requests\RecordBillPaymentRequest;
use Modules\Purchase\Http\Requests\StoreBillRequest;
use Modules\Purchase\Http\Requests\UpdateBillRequest;
use Modules\Purchase\Http\Resources\BillResource;
use Modules\Purchase\Models\Bill;
use Modules\Purchase\Repositories\BillRepository;
use Modules\Purchase\Services\BillService;

class BillController extends Controller
{
    public function __construct(
        private BillService $billService,
        private BillRepository $billRepository
    ) {}

    /**
     * Display a listing of bills.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Bill::class);

        $filters = [
            'status' => $request->input('status'),
            'vendor_id' => $request->input('vendor_id'),
            'purchase_order_id' => $request->input('purchase_order_id'),
            'organization_id' => $request->input('organization_id'),
            'from_date' => $request->input('date_from'),
            'to_date' => $request->input('date_to'),
            'overdue' => $request->input('overdue') === 'true',
            'search' => $request->input('search'),
            'tenant_id' => $request->user()->currentTenant()->id,
        ];

        $perPage = $request->get('per_page', 15);
        $bills = $this->billRepository->getFiltered($filters, $perPage);

        return ApiResponse::paginated(
            $bills->setCollection(
                $bills->getCollection()->map(fn ($bill) => new BillResource($bill))
            ),
            'Bills retrieved successfully'
        );
    }

    /**
     * Store a newly created bill.
     */
    public function store(StoreBillRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['tenant_id'] = $request->user()->currentTenant()->id;
        $data['status'] = BillStatus::DRAFT;
        $data['created_by'] = $request->user()->id;
        $data['paid_amount'] = '0.00';

        $bill = $this->billService->create($data, $items);
        event(new BillCreated($bill));
        $bill->load(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit', 'payments']);

        return ApiResponse::created(
            new BillResource($bill),
            'Bill created successfully'
        );
    }

    /**
     * Display the specified bill.
     */
    public function show(Bill $bill): JsonResponse
    {
        $this->authorize('view', $bill);

        $bill->load(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit', 'payments']);

        return ApiResponse::success(
            new BillResource($bill),
            'Bill retrieved successfully'
        );
    }

    /**
     * Update the specified bill.
     */
    public function update(UpdateBillRequest $request, Bill $bill): JsonResponse
    {
        if ($bill->status !== BillStatus::DRAFT) {
            return ApiResponse::error(
                'Only draft bills can be updated',
                422
            );
        }

        $data = $request->validated();
        $bill = $this->billService->update($bill->id, $data);
        $bill->load(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit', 'payments']);

        return ApiResponse::success(
            new BillResource($bill),
            'Bill updated successfully'
        );
    }

    /**
     * Remove the specified bill.
     */
    public function destroy(Bill $bill): JsonResponse
    {
        $this->authorize('delete', $bill);

        if ($bill->status !== BillStatus::DRAFT) {
            return ApiResponse::error(
                'Only draft bills can be deleted',
                422
            );
        }

        $this->billService->delete($bill->id);

        return ApiResponse::success(
            null,
            'Bill deleted successfully'
        );
    }

    /**
     * Send the bill.
     */
    public function send(Bill $bill): JsonResponse
    {
        $this->authorize('send', $bill);

        if (! in_array($bill->status, [BillStatus::DRAFT, BillStatus::SENT])) {
            return ApiResponse::error(
                'Bill cannot be sent in its current status',
                422
            );
        }

        $bill = $this->billService->send($bill->id);
        event(new BillSent($bill));
        $bill->load(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit', 'payments']);

        return ApiResponse::success(
            new BillResource($bill),
            'Bill sent successfully'
        );
    }

    /**
     * Record payment against the bill.
     */
    public function recordPayment(RecordBillPaymentRequest $request, Bill $bill): JsonResponse
    {
        if (! in_array($bill->status, [BillStatus::SENT, BillStatus::PARTIALLY_PAID])) {
            return ApiResponse::error(
                'Payments can only be recorded for sent or partially paid bills',
                422
            );
        }

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $payment = $this->billService->recordPayment($bill->id, $data);
        $bill->refresh();
        event(new BillPaymentRecorded($payment, $bill));

        if ($bill->status === BillStatus::PAID) {
            event(new BillPaid($bill));
        }

        $bill->load(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit', 'payments']);

        return ApiResponse::success(
            new BillResource($bill),
            'Payment recorded successfully'
        );
    }

    /**
     * Cancel the bill.
     */
    public function cancel(Request $request, Bill $bill): JsonResponse
    {
        $this->authorize('cancel', $bill);

        if (in_array($bill->status, [BillStatus::PAID, BillStatus::CANCELLED])) {
            return ApiResponse::error(
                'Bill cannot be cancelled in its current status',
                422
            );
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $bill = $this->billService->cancel($bill->id, $request->input('reason'));
        event(new BillCancelled($bill));
        $bill->load(['vendor', 'purchaseOrder', 'goodsReceipt', 'items.product', 'items.unit', 'payments']);

        return ApiResponse::success(
            new BillResource($bill),
            'Bill cancelled successfully'
        );
    }
}
