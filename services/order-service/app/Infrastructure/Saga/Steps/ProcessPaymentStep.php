<?php

declare(strict_types=1);

namespace App\Infrastructure\Saga\Steps;

use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\Saga\SagaStepInterface;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPaymentStep
 *
 * Step 3 of the Order Saga.
 * Simulates payment processing.  In production this would call a Payment Service.
 * Compensation: issues a refund / void.
 */
class ProcessPaymentStep implements SagaStepInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly string                   $paymentServiceUrl = '',
    ) {}

    public function name(): string
    {
        return 'process_payment';
    }

    public function execute(array &$context): void
    {
        // In a real implementation, call the Payment Service here.
        // For demonstration we simulate a successful charge.
        $paymentId = 'PAY-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));

        $context['payment_id']     = $paymentId;
        $context['payment_status'] = 'captured';

        // Persist on the order
        $this->orderRepository->update($context['order_id'], [
            'payment_id'     => $paymentId,
            'payment_status' => 'captured',
            'status'         => 'payment_captured',
        ]);

        Log::info("ProcessPaymentStep: Payment [{$paymentId}] captured for order [{$context['order_id']}].");
    }

    public function compensate(array &$context): void
    {
        if (empty($context['payment_id'])) {
            return;
        }

        // In production: call Payment Service to void/refund.
        $this->orderRepository->update($context['order_id'], [
            'payment_status' => 'refunded',
        ]);

        Log::info("ProcessPaymentStep compensation: Payment [{$context['payment_id']}] voided/refunded.");
    }
}
