<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 *
 * Provides health check endpoints for service discovery,
 * load balancers, and monitoring systems.
 */
class HealthController extends Controller
{
    /**
     * Basic liveness check.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'api-gateway',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Detailed health check including dependencies.
     */
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'service' => 'api-gateway',
            'version' => config('app.version', '1.0.0'),
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Check database connectivity.
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'driver' => config('database.default')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Check cache connectivity.
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);
            return ['status' => $ok ? 'ok' : 'error', 'driver' => config('cache.default')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
