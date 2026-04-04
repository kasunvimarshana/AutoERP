<?php
namespace Modules\Accounting\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Application\Contracts\ProcessPaymentServiceInterface;
use Modules\Accounting\Application\Contracts\ProcessRefundServiceInterface;
use Modules\Accounting\Application\DTOs\PaymentData;
use Modules\Accounting\Application\DTOs\RefundData;
use Modules\Accounting\Domain\Repositories\PaymentRepositoryInterface;
use Modules\Accounting\Infrastructure\Http\Resources\PaymentResource;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\PaymentModel;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ProcessPaymentServiceInterface $processPaymentService,
        private readonly ProcessRefundServiceInterface $processRefundService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['status', 'method', 'payable_type', 'payable_id']);
        $perPage  = (int) $request->query('per_page', 15);

        $paginator = $this->paymentRepository->findAll($tenantId, $filters, $perPage);

        return response()->json([
            'data' => PaymentResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'        => 'required|integer',
            'reference_number' => 'required|string|max:100',
            'method'           => 'required|string|in:cash,bank_transfer,card,cheque,credit,other',
            'amount'           => 'required|numeric|min:0.01',
            'currency'         => 'nullable|string|size:3',
            'payable_type'     => 'nullable|string|max:100',
            'payable_id'       => 'nullable|integer',
            'paid_by'          => 'nullable|integer',
            'notes'            => 'nullable|string',
        ]);

        $data = new PaymentData(
            tenantId:        $validated['tenant_id'],
            referenceNumber: $validated['reference_number'],
            method:          $validated['method'],
            amount:          (float) $validated['amount'],
            currency:        $validated['currency']     ?? 'USD',
            payableType:     $validated['payable_type'] ?? null,
            payableId:       $validated['payable_id']   ?? null,
            paidBy:          $validated['paid_by']      ?? null,
            notes:           $validated['notes']        ?? null,
        );

        $payment = $this->processPaymentService->execute($data);

        $model = PaymentModel::find($payment->id);

        return response()->json(new PaymentResource($model), 201);
    }

    public function show(int $id): JsonResponse
    {
        $model = PaymentModel::find($id);

        if ($model === null) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        return response()->json(new PaymentResource($model));
    }

    public function refund(Request $request, int $id): JsonResponse
    {
        $payment = $this->paymentRepository->findById($id);

        if ($payment === null) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'nullable|string|size:3',
            'reason'       => 'nullable|string',
            'processed_by' => 'nullable|integer',
        ]);

        $data = new RefundData(
            tenantId:    $payment->tenantId,
            paymentId:   $id,
            amount:      (float) $validated['amount'],
            currency:    $validated['currency']     ?? $payment->currency,
            reason:      $validated['reason']       ?? null,
            processedBy: $validated['processed_by'] ?? null,
        );

        $refund = $this->processRefundService->execute($data);

        return response()->json([
            'id'           => $refund->id,
            'payment_id'   => $refund->paymentId,
            'amount'       => $refund->amount,
            'currency'     => $refund->currency,
            'status'       => $refund->status,
            'reason'       => $refund->reason,
            'processed_by' => $refund->processedBy,
            'processed_at' => $refund->processedAt,
        ], 201);
    }
}
