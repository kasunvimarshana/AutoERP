<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthController extends BaseController
{
    /**
     * GET /api/health
     * Returns service health status including DB and downstream service checks.
     */
    public function __invoke(): JsonResponse
    {
        $checks  = [];
        $healthy = true;

        // ---------------------------------------------------------------
        // 1. Database connectivity
        // ---------------------------------------------------------------
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
            $healthy             = false;
        }

        // ---------------------------------------------------------------
        // 2. Inventory Service
        // ---------------------------------------------------------------
        $checks['inventory_service'] = $this->checkExternalService(
            config('services.inventory_service.url', 'http://inventory-service'),
            '/api/health'
        );

        if ($checks['inventory_service']['status'] !== 'ok') {
            $healthy = false;
        }

        // ---------------------------------------------------------------
        // 3. Product Service
        // ---------------------------------------------------------------
        $checks['product_service'] = $this->checkExternalService(
            config('services.product_service.url', 'http://product-service'),
            '/api/health'
        );

        if ($checks['product_service']['status'] !== 'ok') {
            $healthy = false;
        }

        // ---------------------------------------------------------------
        // 4. Payment Service (non-critical – degraded vs failed)
        // ---------------------------------------------------------------
        $checks['payment_service'] = $this->checkExternalService(
            config('services.payment_service.url', 'http://payment-service'),
            '/api/health'
        );

        $statusCode = $healthy ? 200 : 503;

        return response()->json([
            'success' => $healthy,
            'service' => 'order-service',
            'status'  => $healthy ? 'healthy' : 'degraded',
            'checks'  => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
    }

    private function checkExternalService(string $baseUrl, string $path): array
    {
        try {
            $response = Http::timeout(5)->get(rtrim($baseUrl, '/') . $path);

            if ($response->successful()) {
                return ['status' => 'ok', 'response_time_ms' => $response->transferStats?->getTransferTime() * 1000];
            }

            return ['status' => 'error', 'http_status' => $response->status()];
        } catch (\Throwable $e) {
            Log::debug('HealthController: external check failed', ['url' => $baseUrl . $path, 'error' => $e->getMessage()]);

            return ['status' => 'unreachable', 'message' => $e->getMessage()];
        }
    }
}
