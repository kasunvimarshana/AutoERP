<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Health;

use App\Http\Controllers\Controller;
use App\Infrastructure\MessageBroker\Contracts\MessageBrokerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Health check endpoints for service monitoring and load-balancer probes.
 *
 * GET /api/v1/health       — liveness probe
 * GET /api/v1/health/ready — readiness probe (checks all dependencies)
 */
final class HealthController extends Controller
{
    public function __construct(
        private readonly MessageBrokerInterface $messageBroker
    ) {}

    /**
     * Liveness probe — returns 200 when the process is alive.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service'   => config('app.name'),
            'version'   => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Readiness probe — checks all external dependencies.
     *
     * Returns 200 when all checks pass, 503 when any check fails.
     */
    public function readiness(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database check
        $checks['database'] = $this->checkDatabase();

        // Cache check
        $checks['cache'] = $this->checkCache();

        // Message broker check
        $checks['broker'] = $this->checkBroker();

        // Redis check
        $checks['redis'] = $this->checkRedis();

        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $healthy = false;
                break;
            }
        }

        return response()->json([
            'status'    => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::selectOne('SELECT 1');
            return ['status' => 'ok', 'latency_ms' => $this->measureMs(fn () => DB::selectOne('SELECT 1'))];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = '_health_check_' . uniqid('', true);
            Cache::put($key, true, 5);
            $result = Cache::get($key);
            Cache::forget($key);

            return $result === true
                ? ['status' => 'ok']
                : ['status' => 'error', 'message' => 'Cache read/write mismatch'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkBroker(): array
    {
        try {
            $healthy = $this->messageBroker->isHealthy();

            return $healthy
                ? ['status' => 'ok', 'driver' => $this->messageBroker->getDriver()]
                : ['status' => 'error', 'driver' => $this->messageBroker->getDriver(), 'message' => 'Broker unhealthy'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function measureMs(callable $fn): float
    {
        $start = microtime(true);
        $fn();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
