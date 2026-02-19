<?php

declare(strict_types=1);

namespace Modules\Billing\Services;

use Modules\Billing\Enums\PaymentStatus;
use Modules\Billing\Exceptions\PaymentFailedException;
use Modules\Billing\Models\Subscription;
use Modules\Billing\Models\SubscriptionPayment;
use Modules\Billing\Repositories\SubscriptionPaymentRepository;
use Modules\Billing\Repositories\SubscriptionRepository;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;

/**
 * Payment Service
 *
 * Handles payment processing, recording, and refund logic.
 */
class PaymentService
{
    public function __construct(
        private SubscriptionPaymentRepository $paymentRepository,
        private SubscriptionRepository $subscriptionRepository,
        private CodeGeneratorService $codeGenerator,
        private PaymentGatewayService $paymentGateway
    ) {}

    /**
     * Create a payment record for a subscription.
     */
    public function createPayment(int $subscriptionId, array $data): SubscriptionPayment
    {
        $subscription = $this->subscriptionRepository->findOrFail($subscriptionId);

        return TransactionHelper::execute(function () use ($subscription, $data) {
            if (empty($data['payment_code'])) {
                $data['payment_code'] = $this->generatePaymentCode();
            }

            $data['subscription_id'] = $subscription->id;
            $data['tenant_id'] = $subscription->tenant_id;
            $data['status'] = $data['status'] ?? PaymentStatus::Pending;
            $data['amount'] = $data['amount'] ?? $subscription->total_amount;

            return $this->paymentRepository->create($data);
        });
    }

    /**
     * Process a payment via payment gateway (Stripe, PayPal, Razorpay).
     */
    public function processPayment(int $paymentId, array $paymentData): SubscriptionPayment
    {
        $payment = $this->paymentRepository->findOrFail($paymentId);

        if ($payment->status->isFinal()) {
            throw new PaymentFailedException('Payment already processed');
        }

        return TransactionHelper::execute(function () use ($payment, $paymentData) {
            // Update payment to processing
            $payment->update(['status' => PaymentStatus::Processing]);

            try {
                // Process payment via configured gateway (Stripe, PayPal, Razorpay)
                $gatewayData = [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency ?? config('billing.currency', 'USD'),
                    'description' => "Subscription payment for {$payment->subscription->plan->name}",
                    'metadata' => [
                        'subscription_id' => $payment->subscription_id,
                        'payment_id' => $payment->id,
                        'tenant_id' => $payment->tenant_id,
                    ],
                    ...$paymentData, // Merge any additional payment data
                ];

                $result = $this->paymentGateway->processPayment($gatewayData);

                // Update payment with gateway response
                $payment->update([
                    'status' => $result['success'] ? PaymentStatus::Succeeded : PaymentStatus::Failed,
                    'transaction_id' => $result['transaction_id'],
                    'payment_method' => $paymentData['payment_method'] ?? 'card',
                    'payment_gateway' => $result['provider'],
                    'paid_at' => $result['success'] ? now() : null,
                    'metadata' => array_merge(
                        $payment->metadata ?? [],
                        ['gateway_response' => $result]
                    ),
                ]);

                if (!$result['success']) {
                    throw new PaymentFailedException('Payment gateway returned failure status');
                }

                return $payment->fresh();
            } catch (\Exception $e) {
                // Handle payment failure
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'error_message' => $e->getMessage(),
                ]);

                throw new PaymentFailedException($e->getMessage(), 0, $e);
            }
        });
    }

    /**
     * Record a successful payment.
     */
    public function recordSuccessfulPayment(int $paymentId, string $transactionId): SubscriptionPayment
    {
        $payment = $this->paymentRepository->findOrFail($paymentId);

        return TransactionHelper::execute(function () use ($payment, $transactionId) {
            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'transaction_id' => $transactionId,
                'paid_at' => now(),
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Record a failed payment.
     */
    public function recordFailedPayment(int $paymentId, string $errorMessage): SubscriptionPayment
    {
        $payment = $this->paymentRepository->findOrFail($paymentId);

        return TransactionHelper::execute(function () use ($payment, $errorMessage) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'error_message' => $errorMessage,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Refund a payment via payment gateway.
     */
    public function refundPayment(int $paymentId, ?string $amount = null): SubscriptionPayment
    {
        $payment = $this->paymentRepository->findOrFail($paymentId);

        if (! $payment->isSuccessful()) {
            throw new PaymentFailedException('Cannot refund non-successful payment');
        }

        return TransactionHelper::execute(function () use ($payment, $amount) {
            $refundAmount = $amount ?? $payment->amount;

            // Process refund via payment gateway
            try {
                $result = $this->paymentGateway->processRefund(
                    $payment->transaction_id,
                    $refundAmount
                );

                if ($refundAmount === $payment->amount) {
                    $status = PaymentStatus::Refunded;
                } else {
                    $status = PaymentStatus::PartiallyRefunded;
                }

                $payment->update([
                    'status' => $status,
                    'refunded_amount' => $refundAmount,
                    'metadata' => array_merge(
                        $payment->metadata ?? [],
                        ['refund_response' => $result]
                    ),
                ]);

                return $payment->fresh();
            } catch (\Exception $e) {
                throw new PaymentFailedException("Refund failed: {$e->getMessage()}", 0, $e);
            }
        });
    }

    /**
     * Generate unique payment code.
     */
    private function generatePaymentCode(): string
    {
        $prefix = config('billing.payment_code_prefix', 'PAY-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->paymentRepository->findByCode($code) !== null
        );
    }
}
