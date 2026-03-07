<?php

namespace App\Saga\Steps;

use App\Models\Order;
use App\Saga\Contracts\SagaStepInterface;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;

class ProcessPaymentStep implements SagaStepInterface
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function getName(): string { return 'process_payment'; }

    public function execute(Order $order, array $context = []): array
    {
        $result = $this->paymentService->charge($order->id, $order->total_amount, $order->tenant_id);

        if (!$result['success']) {
            throw new \RuntimeException("Payment failed: " . $result['message']);
        }

        return ['payment_id' => $result['payment_id'], 'payment_status' => $result['status']];
    }

    public function compensate(Order $order, array $context = []): void
    {
        $paymentId = $context['payment_id'] ?? null;
        if ($paymentId) {
            try {
                $this->paymentService->refund($paymentId, $order->total_amount);
            } catch (\Exception $e) {
                Log::error('ProcessPaymentStep compensate failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            }
        }
    }
}
