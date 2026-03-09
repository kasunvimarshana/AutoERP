<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 */
class HealthController extends Controller
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 5]);
    }

    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'saga-orchestrator',
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'downstream_services' => $this->checkDownstreamServices(),
        ];
        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'service' => 'saga-orchestrator',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'hc_' . time();
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);
            return ['status' => $ok ? 'ok' : 'error'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkDownstreamServices(): array
    {
        $services = [
            'order-service' => config('services.order_service') . '/health/ping',
            'inventory-service' => config('services.inventory_service') . '/health/ping',
            'payment-service' => config('services.payment_service') . '/health/ping',
            'notification-service' => config('services.notification_service') . '/health/ping',
        ];

        $results = [];
        foreach ($services as $name => $url) {
            try {
                $response = $this->httpClient->get($url);
                $results[$name] = ['status' => 'ok', 'http_status' => $response->getStatusCode()];
            } catch (\Throwable $e) {
                $results[$name] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $allOk = collect($results)->every(fn ($r) => $r['status'] === 'ok');
        return array_merge(['status' => $allOk ? 'ok' : 'degraded'], $results);
    }
}
