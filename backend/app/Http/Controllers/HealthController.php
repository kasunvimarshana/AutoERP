<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends BaseController
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'redis'    => $this->checkRedis(),
            'storage'  => $this->checkStorage(),
        ];

        $allHealthy = collect($checks)->every(fn ($c) => $c['status'] === 'healthy');

        return response()->json([
            'status'    => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version'   => config('app.version', '1.0.0'),
            'checks'    => $checks,
        ], $allHealthy ? 200 : 503);
    }

    public function ping(): JsonResponse
    {
        return response()->json(['pong' => true, 'timestamp' => now()->toIso8601String()]);
    }

    public function readiness(): JsonResponse
    {
        // Check critical dependencies for readiness
        $dbOk = $this->checkDatabase()['status'] === 'healthy';

        return response()->json(
            ['ready' => $dbOk, 'timestamp' => now()->toIso8601String()],
            $dbOk ? 200 : 503
        );
    }

    public function liveness(): JsonResponse
    {
        return response()->json(['alive' => true, 'timestamp' => now()->toIso8601String()]);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return ['status' => 'healthy', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = '_health_check_' . time();
            Cache::put($key, true, 5);
            $value = Cache::get($key);
            Cache::forget($key);

            return ['status' => $value ? 'healthy' : 'unhealthy'];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return ['status' => 'healthy', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path('logs');
            $writable = is_writable($path);

            return ['status' => $writable ? 'healthy' : 'unhealthy', 'path' => $path];
        } catch (\Throwable $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }
}
