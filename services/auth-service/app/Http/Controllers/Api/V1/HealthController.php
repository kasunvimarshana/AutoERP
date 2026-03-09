<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
            'queue'    => $this->checkQueue(),
            'redis'    => $this->checkRedis(),
        ];

        $allHealthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'success' => $allHealthy,
            'status'  => $allHealthy ? 'healthy' : 'degraded',
            'service' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
            'checks'  => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'ok', 'message' => 'Database connection healthy.'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . now()->timestamp;
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value === 'ok') {
                return ['status' => 'ok', 'message' => 'Cache operational.'];
            }

            return ['status' => 'error', 'message' => 'Cache read/write failed.'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Cache check failed: ' . $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            if ($driver === 'sync') {
                return ['status' => 'ok', 'message' => 'Queue driver: sync.'];
            }

            // For RabbitMQ: check connection size returns without exception
            $size = Queue::size();
            return ['status' => 'ok', 'message' => "Queue driver: {$driver}, size: {$size}."];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $pong = Redis::ping('health');
            return ['status' => 'ok', 'message' => 'Redis operational.'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Redis check failed: ' . $e->getMessage()];
        }
    }
}
