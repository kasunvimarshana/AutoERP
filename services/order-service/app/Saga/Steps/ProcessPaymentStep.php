<?php

namespace App\Saga\Steps;

use App\Saga\Contracts\SagaStepInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessPaymentStep implements SagaStepInterface
{
    public function getName(): string
    {
        return 'process_payment';
    }

    public function execute(array $context): array
    {
        $tenantConfig = $context['tenant_config'] ?? [];
        $paymentGateway = $tenantConfig['payment_gateway'] ?? config('services.payment.default', 'mock');
        $total = $context['order']['total'] ?? 0;
        $currency = $context['order']['currency'] ?? 'USD';
        $paymentMethod = $context['order']['payment_method'] ?? 'card';

        if ($paymentGateway === 'mock' || app()->environment('testing')) {
            $paymentReference = 'PAY-MOCK-' . strtoupper(uniqid());
            $context['payment_reference'] = $paymentReference;
            $context['payment_status'] = 'paid';
            return $context;
        }

        $gatewayUrl = $tenantConfig['payment_gateway_url'] ?? config('services.payment.url');
        $gatewayKey = $tenantConfig['payment_gateway_key'] ?? config('services.payment.key');

        $response = Http::withToken($gatewayKey)
            ->timeout(30)
            ->post("{$gatewayUrl}/charge", [
                'amount'         => (int) round($total * 100),
                'currency'       => strtolower($currency),
                'payment_method' => $paymentMethod,
                'metadata'       => [
                    'order_id'  => $context['order_id'] ?? null,
                    'tenant_id' => $context['tenant_id'] ?? null,
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Payment failed: ' . ($response->json('message') ?? $response->status())
            );
        }

        $context['payment_reference'] = $response->json('id') ?? $response->json('reference');
        $context['payment_status']    = 'paid';

        return $context;
    }

    public function compensate(array $context): void
    {
        $paymentReference = $context['payment_reference'] ?? null;

        if (!$paymentReference || str_starts_with($paymentReference, 'PAY-MOCK-')) {
            return;
        }

        $tenantConfig = $context['tenant_config'] ?? [];
        $gatewayUrl   = $tenantConfig['payment_gateway_url'] ?? config('services.payment.url');
        $gatewayKey   = $tenantConfig['payment_gateway_key'] ?? config('services.payment.key');

        try {
            Http::withToken($gatewayKey)
                ->timeout(30)
                ->post("{$gatewayUrl}/refund", [
                    'payment_id' => $paymentReference,
                    'reason'     => 'Order saga compensation',
                ]);
        } catch (\Throwable $e) {
            Log::error('ProcessPaymentStep compensation failed', [
                'payment_reference' => $paymentReference,
                'error'             => $e->getMessage(),
            ]);
        }
    }
}
