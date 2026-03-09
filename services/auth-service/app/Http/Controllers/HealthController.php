<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Controller
 *
 * Provides health status endpoints for container orchestration and monitoring.
 */
class HealthController extends Controller
{
    /**
     * Overall health check.
     */
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'service' => 'auth-service',
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /**
     * Liveness probe - is the service running?
     */
    public function live(): JsonResponse
    {
        return response()->json(['status' => 'alive']);
    }

    /**
     * Readiness probe - is the service ready to serve traffic?
     */
    public function ready(): JsonResponse
    {
        $dbOk = $this->checkDatabase()['status'] === 'ok';

        return response()->json(
            ['status' => $dbOk ? 'ready' : 'not_ready'],
            $dbOk ? 200 : 503
        );
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'ok', 'latency_ms' => 0];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => 'ok'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
