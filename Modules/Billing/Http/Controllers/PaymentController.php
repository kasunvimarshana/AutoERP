<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\ProcessPaymentRequest;
use Modules\Billing\Http\Resources\SubscriptionPaymentResource;
use Modules\Billing\Models\SubscriptionPayment;
use Modules\Billing\Repositories\SubscriptionPaymentRepository;
use Modules\Billing\Services\PaymentService;
use Modules\Core\Http\Responses\ApiResponse;

class PaymentController extends Controller
{
    public function __construct(
        private SubscriptionPaymentRepository $paymentRepository,
        private PaymentService $paymentService
    ) {}

    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SubscriptionPayment::class);

        $filters = [
            'status' => $request->status,
            'subscription_id' => $request->subscription_id,
            'payment_gateway' => $request->payment_gateway,
            'search' => $request->search,
        ];

        $perPage = $request->get('per_page', 15);
        $payments = $this->paymentRepository->searchPayments(
            array_filter($filters, fn ($value) => ! is_null($value)),
            $perPage
        );

        return ApiResponse::paginated(
            $payments->setCollection(
                $payments->getCollection()->map(fn ($payment) => new SubscriptionPaymentResource($payment))
            ),
            'Payments retrieved successfully'
        );
    }

    /**
     * Display the specified payment.
     */
    public function show(int $id): JsonResponse
    {
        $payment = $this->paymentRepository->findOrFail($id);
        $this->authorize('view', $payment);

        $payment->load('subscription.plan');

        return ApiResponse::success(
            new SubscriptionPaymentResource($payment),
            'Payment retrieved successfully'
        );
    }

    /**
     * Process a payment.
     */
    public function process(ProcessPaymentRequest $request, int $id): JsonResponse
    {
        $payment = $this->paymentRepository->findOrFail($id);
        $this->authorize('update', $payment);

        $payment = $this->paymentService->processPayment($id, $request->validated());

        return ApiResponse::success(
            new SubscriptionPaymentResource($payment->load('subscription.plan')),
            'Payment processed successfully'
        );
    }

    /**
     * Refund a payment.
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $payment = $this->paymentRepository->findOrFail($id);
        $this->authorize('update', $payment);

        $request->validate(['amount' => 'nullable|numeric|min:0']);

        $payment = $this->paymentService->refundPayment($id, $request->amount);

        return ApiResponse::success(
            new SubscriptionPaymentResource($payment->load('subscription.plan')),
            'Payment refunded successfully'
        );
    }
}
